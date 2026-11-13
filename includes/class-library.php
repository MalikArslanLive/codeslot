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
		$page       = isset( $_GET['library_page'] ) ? max( 1, absint( $_GET['library_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
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
		$pages    = max( 1, (int) ceil( count( $matching ) / 12 ) );
		$page     = min( $pages, $page );
		echo '<section class="codeslot-panel"><h2>Local Snippet Library</h2><p>' . absint( count( $all ) ) . ' original bundled recipes and configuration guides. All free, no account or cloud connection. Executable recipes open as inactive drafts for code review. Configuration guides are labeled manual setup and never offered for activation. Example values, themes and companion requirements must be checked before activation. The count describes bundled recipes, not a claim of universal compatibility.</p><form method="get" class="codeslot-filters"><input type="hidden" name="page" value="codeslot-library"><label>Search recipes<input name="library_search" value="' . esc_attr( $search ) . '"></label><label>Category<select name="library_category"><option value="">All categories</option>';
		foreach ( $categories as $item ) {
			echo '<option value="' . esc_attr( $item ) . '" ' . selected( $category, $item, false ) . '>' . esc_html( $item ) . '</option>';
		}
		echo '</select></label><button class="button">Filter library</button></form><p>' . absint( count( $matching ) ) . ' matching recipes</p><div class="codeslot-library-grid">';
		foreach ( array_slice( $matching, ( $page - 1 ) * 12, 12, true ) as $key => $recipe ) {
			if ( ! empty( $recipe['manual'] ) ) {
				echo '<article class="codeslot-recipe"><h3>' . esc_html( $recipe['name'] ) . '</h3><p><strong>Manual wp-config.php setup — not an executable library snippet</strong></p><p>' . esc_html( $recipe['description'] ) . '</p><pre>' . esc_html( $recipe['code'] ) . '</pre></article>';
				continue;
			}
			echo '<article class="codeslot-recipe"><h3>' . esc_html( $recipe['name'] ) . '</h3><p>' . esc_html( isset( $recipe['description'] ) ? $recipe['description'] : 'Review and customize this local example before activation.' ) . '</p><p><span class="codeslot-type">' . esc_html( $recipe['type'] . ' / ' . $recipe['location'] ) . '</span></p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=codeslot-new&template=' . $key ) ) . '">Review and use snippet</a><details><summary>Preview code</summary><pre>' . esc_html( $recipe['code'] ) . '</pre></details></article>';
		}
		echo '</div><nav aria-label="Library pages"><p>';
		foreach ( array(
			'Previous' => $page - 1,
			'Next'     => $page + 1,
		) as $label => $number ) {
			if ( $number > 0 && $number <= $pages ) {
				echo '<a class="button" href="' . esc_url(
					add_query_arg(
						array(
							'page'             => 'codeslot-library',
							'library_search'   => $search,
							'library_category' => $category,
							'library_page'     => $number,
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html( $label ) . '</a> ';
			}
		}
		echo 'Page ' . absint( $page ) . ' / ' . absint( $pages ) . '</p></nav></section>';
	}
}
