<?php

namespace Elementor\Modules\GlobalClasses;

use Elementor\Core\Utils\Collection;
use Elementor\Modules\AtomicWidgets\Parsers\Parse_Result;
use Elementor\Modules\AtomicWidgets\Styles\Style_Schema;
use Elementor\Modules\AtomicWidgets\Parsers\Style_Parser;
use Elementor\Modules\GlobalClasses\Utils\Error_Builder;
use Elementor\Modules\GlobalClasses\Utils\Response_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Global_Classes_REST_API {
	const API_NAMESPACE = 'elementor/v1';
	const API_BASE = 'global-classes';

	private $repository = null;

	public function register_hooks() {
		add_action( 'rest_api_init', fn() => $this->register_routes() );
	}

	private function get_repository() {
		if ( ! $this->repository ) {
			$this->repository = new Global_Classes_Repository();
		}

		return $this->repository;
	}

	/**
	 * TODO: Add sanitization when implemented on prop types [EDS-574]
	 */
	private function register_routes() {
		register_rest_route( self::API_NAMESPACE, '/' . self::API_BASE, [
			[
				'methods' => 'GET',
				'callback' => fn() => $this->route_wrapper( fn() => $this->all() ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			],
		] );

		register_rest_route( self::API_NAMESPACE, '/' . self::API_BASE, [
			[
				'methods' => 'PUT',
				'callback' => fn( $request ) => $this->route_wrapper( fn() => $this->put( $request ) ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'args' => [
					'items' => [
						'required' => true,
						'type' => 'object',
						'additionalProperties' => false,
						'patternProperties' => [
							'^g-[a-z0-9]+$' => [
								'type' => 'object',
								'properties' => [
									'id' => [
										'type' => 'string',
										'pattern' => '^g-[a-z0-9]+$',
										'required' => true,
									],
									'variants' => [
										'type' => 'array',
										'required' => true,
									],
									'type' => [
										'type' => 'string',
										'enum' => [ 'class' ],
										'required' => true,
									],
									'label' => [
										'type' => 'string',
										'required' => true,
									],
								],
							],
						],
					],
					'order' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'string',
							'pattern' => '^g-[a-z0-9]+$',
						],
					],
				],
			],
		] );
	}

	private function all() {
		$classes = $this->get_repository()->all();

		return Response_Builder::make( (object) $classes->get_items()->all() )
			->set_meta( [ 'order' => $classes->get_order()->all() ] )
			->build();
	}

	private function put( \WP_REST_Request $request ) {
		$items = $request->get_param( 'items' );

		$parsed_items = $this->parse_items( $items );

		if ( ! $parsed_items->is_valid() ) {
			return Error_Builder::make( 'invalid_items' )
				->set_status( 400 )
				->set_message( 'Invalid items: ' . $parsed_items->errors()->to_string() )
				->build();
		}

		$order = $request->get_param( 'order' );

		if ( ! $this->is_valid_order( $order, $parsed_items->unwrap() ) ) {
			return Error_Builder::make( 'invalid_order' )
				->set_status( 400 )
				->set_message( 'Invalid order' )
				->build();
		}

		$this->get_repository()->put(
			$parsed_items->unwrap(),
			$order
		);

		return Response_Builder::make()->no_content()->build();
	}

	private function parse_items( array $items ) {
		$result = Parse_Result::make();
		$sanitized_items = [];

		foreach ( $items as $item_id => $item ) {
			$parsed_item = Style_Parser::make( Style_Schema::get() )->parse( $item );

			if ( ! $parsed_item->is_valid() ) {
				$result->errors()->merge( $parsed_item->errors() );
				continue;
			}

			$sanitized_item = $parsed_item->unwrap();

			if ( $item_id !== $sanitized_item['id'] ) {
				$result->errors()->add( "$item_id.id", 'mismatching_value' );

				continue;
			}

			$sanitized_items[ $sanitized_item['id'] ] = $sanitized_item;
		}

		return $result->wrap( $sanitized_items );
	}

	private function is_valid_order( array $order, array $items ) {
		$existing_ids = array_keys( $items );

		$excess_ids = Collection::make( $order )->diff( $existing_ids );
		$missing_ids = Collection::make( $existing_ids )->diff( $order );

		$has_duplications = Collection::make( $order )->unique()->all() !== $order;

		return (
			$excess_ids->is_empty() &&
			$missing_ids->is_empty() &&
			! $has_duplications
		);
	}

	private function route_wrapper( callable $cb ) {
		try {
			$response = $cb();
		} catch ( \Exception $e ) {
			return Error_Builder::make( 'unexpected_error' )
				->set_message( __( 'Something went wrong', 'elementor' ) )
				->build();
		}

		return $response;
	}
}
