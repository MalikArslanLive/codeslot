<?php
/** Original offline recipe catalog, filterable without remote accounts. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Library {
	public static function all() {
		return require dirname( CODESLOT_FILE ) . '/templates/library.php';
	}

	public static function page() {
		$all        = self::all();
		$search     = isset( $_GET['library_search'] ) && is_string( $_GET['library_search'] ) ? sanitize_text_field( wp_unslash( $_GET['library_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		$category   = isset( $_GET['library_category'] ) ? sanitize_text_field( wp_unslash( $_GET['library_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		$categories = array();
		foreach ( $all as $recipe ) {
			$categories = array_merge( $categories, isset( $recipe['categories'] ) ? $recipe['categories'] : array( 'Examples' ) );
		}
		$categories = array_unique( $categories );
		sort( $categories );
		$matching = array_filter(
			$all,
			static function ( $recipe ) use ( $search, $category ) {
				$description = isset( $recipe['description'] ) ? $recipe['description'] : '';
				return ( '' === $search || false !== stripos( $recipe['name'] . ' ' . $description, $search ) ) && ( '' === $category || in_array( $category, isset( $recipe['categories'] ) ? $recipe['categories'] : array( 'Examples' ), true ) );
			}
		);
		echo '<section class="codeslot-panel"><h2>Local Snippet Library</h2><p>' . absint( count( $all ) ) . ' original bundled recipes and configuration guides. Browse the complete local collection. Preview a recipe, customize it and save an inactive copy before enabling it. Configuration guides include their own setup instructions.</p><form method="get" class="codeslot-filters" id="codeslot-library-filter"><input type="hidden" name="page" value="codeslot-library"><label>Search recipes<input name="library_search" value="' . esc_attr( $search ) . '"></label><label>Category<select name="library_category"><option value="">All categories</option>';
		foreach ( $categories as $item ) {
			echo '<option value="' . esc_attr( $item ) . '" ' . selected( $category, $item, false ) . '>' . esc_html( $item ) . '</option>';
		}
		echo '</select></label><button class="button">Filter library</button><button type="button" class="button" id="codeslot-library-reset">Clear filters</button></form><p>' . '<span id="codeslot-library-count" aria-live="polite">' . absint( count( $matching ) ) . ' recipes shown</span></p><p id="codeslot-library-empty" hidden>No recipes match your filters.</p><div class="codeslot-library-grid">';
		foreach ( $all as $key => $recipe ) {
			echo '<article class="codeslot-recipe" data-library-search="' . esc_attr( $recipe['name'] . ' ' . ( isset( $recipe['description'] ) ? $recipe['description'] : '' ) ) . '" data-library-categories="' . esc_attr( wp_json_encode( isset( $recipe['categories'] ) ? $recipe['categories'] : array( 'Examples' ) ) ) . '"' . ( isset( $matching[ $key ] ) ? '' : ' hidden' ) . '>';
			if ( ! empty( $recipe['manual'] ) ) {
				echo '<h3>' . esc_html( $recipe['name'] ) . '</h3><p><strong>Manual wp-config.php setup — not an executable library snippet</strong></p><p>' . esc_html( $recipe['description'] ) . '</p><pre>' . esc_html( $recipe['code'] ) . '</pre></article>';
				continue;
			}
			echo '<h3>' . esc_html( $recipe['name'] ) . '</h3><p>' . esc_html( isset( $recipe['description'] ) ? $recipe['description'] : 'Review and customize this local example before activation.' ) . '</p><p><span class="codeslot-type">' . esc_html( $recipe['type'] . ' / ' . $recipe['location'] ) . '</span></p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=codeslot-new&template=' . $key ) ) . '">Review and use snippet</a><details><summary>Preview code</summary><pre>' . esc_html( $recipe['code'] ) . '</pre></details></article>';
		}
		echo '</div></section>';
	}
}
