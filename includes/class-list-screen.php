<?php
/** Native per-user Screen Options and separate list columns. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class List_Screen {
	public static function boot() {
		add_filter( 'manage_toplevel_page_codeslot_columns', array( self::class, 'columns' ) );
		add_filter( 'screen_settings', array( self::class, 'settings' ), 10, 2 );
		add_action( 'admin_init', array( self::class, 'save' ) );
		add_filter(
			'default_hidden_columns',
			static function ( $hidden, $screen ) {
				return 'toplevel_page_codeslot' === $screen->id ? array( 'updated', 'shortcode', 'note' ) : $hidden;
			},
			10,
			2
		);
	}

	public static function columns() {
		return array(
			'cb'         => 'Select',
			'title'      => 'Name',
			'id'         => 'ID',
			'author'     => 'Author',
			'location'   => 'Location',
			'created'    => 'Created',
			'updated'    => 'Last Updated',
			'categories' => 'Categories',
			'tags'       => 'Tags',
			'shortcode'  => 'Shortcode',
			'type'       => 'Code Type',
			'priority'   => 'Priority',
			'note'       => 'Note',
			'status'     => 'Status',
		);
	}

	public static function preferences() {
		$value = get_user_meta( get_current_user_id(), 'codeslot_list_preferences', true );
		return wp_parse_args(
			is_array( $value ) ? $value : array(),
			array(
				'per_page'  => 30,
				'sort'      => 'priority',
				'direction' => 'ASC',
			)
		);
	}

	public static function settings( $html, $screen ) {
		if ( 'toplevel_page_codeslot' !== $screen->id || ! Permissions::allowed() ) {
			return $html; }
		$p     = self::preferences();
		$html .= '<fieldset class="codeslot-screen-settings"><legend>Snippet list preferences</legend><input type="hidden" name="codeslot_screen" value="1">' . wp_nonce_field( 'codeslot_screen', 'codeslot_screen_nonce', true, false ) . '<p><label>Snippets per page <input type="number" min="1" max="100" name="codeslot_per_page" value="' . absint( $p['per_page'] ) . '"></label></p><p><label>Order snippets by <select name="codeslot_sort">';
		foreach ( array(
			'priority' => 'Priority',
			'title'    => 'Name',
			'date'     => 'Created',
			'modified' => 'Last Updated',
			'ID'       => 'ID',
		) as $key => $label ) {
			$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $p['sort'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $html . '</select></label> <label>Direction <select name="codeslot_direction"><option value="ASC" ' . selected( $p['direction'], 'ASC', false ) . '>Ascending</option><option value="DESC" ' . selected( $p['direction'], 'DESC', false ) . '>Descending</option></select></label></p><button class="button button-primary" type="submit">Apply list preferences</button></fieldset>';
	}

	public static function save() {
		if ( empty( $_POST['codeslot_screen'] ) ) {
			return; }
		if ( ! Permissions::allowed() ) {
			wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'codeslot_screen', 'codeslot_screen_nonce' );
		$sort = isset( $_POST['codeslot_sort'] ) && in_array( $_POST['codeslot_sort'], array( 'priority', 'title', 'date', 'modified', 'ID' ), true ) ? sanitize_text_field( wp_unslash( $_POST['codeslot_sort'] ) ) : 'priority';
		update_user_meta(
			get_current_user_id(),
			'codeslot_list_preferences',
			array(
				'per_page'  => max( 1, min( 100, isset( $_POST['codeslot_per_page'] ) ? absint( $_POST['codeslot_per_page'] ) : 30 ) ),
				'sort'      => $sort,
				'direction' => isset( $_POST['codeslot_direction'] ) && 'DESC' === $_POST['codeslot_direction'] ? 'DESC' : 'ASC',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=codeslot' ) );
		exit;
	}

	public static function headers() {
		$hidden = get_hidden_columns( get_current_screen() );
		foreach ( self::columns() as $key => $label ) {
			$class = 'cb' === $key ? 'check-column' : 'manage-column column-' . $key . ( in_array( $key, $hidden, true ) ? ' hidden' : '' );
			echo '<th id="' . esc_attr( $key ) . '" scope="col" class="' . esc_attr( $class ) . '">';
			if ( 'cb' === $key ) {
				echo '<input type="checkbox" id="codeslot-select-all" aria-label="Select all snippets on this page">';
			} else {
				echo esc_html( $label ); }
			echo '</th>';
		}
	}

	public static function row( $s, $post ) {
		$name      = '' !== trim( $s['name'] ) ? $s['name'] : sprintf( 'Untitled snippet #%d', $post->ID );
		$s['name'] = $name;
		$edit      = admin_url( 'admin.php?page=codeslot-new&id=' . $post->ID );
		$quick     = wp_json_encode( array_intersect_key( $s, array_flip( array( 'id', 'name', 'description', 'priority', 'active', 'categories', 'tags', 'location' ) ) ) );
		$hidden    = get_hidden_columns( get_current_screen() );
		echo '<tr>';
		foreach ( self::columns() as $key => $label ) {
			$class = 'cb' === $key ? 'check-column' : 'column-' . $key . ( 'title' === $key ? ' codeslot-name column-primary' : '' ) . ( in_array( $key, $hidden, true ) ? ' hidden' : '' );
			echo '<td class="' . esc_attr( $class ) . '" data-label="' . esc_attr( $label ) . '" data-colname="' . esc_attr( $label ) . '">';
			switch ( $key ) {
				case 'cb':
					echo '<input type="checkbox" aria-label="' . esc_attr( 'Select ' . $name ) . '" name="ids[]" value="' . absint( $post->ID ) . '">';
					break;
				case 'title':
					if ( ! empty( $s['trashed'] ) ) {
						echo '<strong>' . esc_html( $name ) . '</strong><div class="codeslot-row-actions"><button type="button" class="button-link codeslot-remove" data-task="restore" data-id="' . absint( $post->ID ) . '" data-name="' . esc_attr( $name ) . '">Restore</button> <button type="button" class="button-link codeslot-remove" data-task="delete" data-id="' . absint( $post->ID ) . '" data-name="' . esc_attr( $name ) . '">Delete Permanently</button></div>';
						break;
					}
					echo '<strong><a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a></strong>' . ( $s['locked'] ? ' <span class="codeslot-lock">Locked</span>' : '' ) . '<div class="codeslot-row-actions"><a class="codeslot-edit" aria-label="' . esc_attr( 'Edit ' . $name ) . '" href="' . esc_url( $edit ) . '">Edit</a> <button type="button" class="button-link codeslot-quick" data-snippet="' . esc_attr( $quick ) . '" aria-controls="codeslot-quick-edit" aria-expanded="false"' . disabled( $s['locked'], true, false ) . '>Quick Edit</button> <button type="button" class="button-link codeslot-remove" data-task="trash" data-id="' . absint( $post->ID ) . '" data-name="' . esc_attr( $name ) . '"' . disabled( $s['locked'], true, false ) . '>Trash</button></div>';
					break;
				case 'status':
					if ( ! empty( $s['trashed'] ) ) {
						echo '<span class="codeslot-status">Trashed</span>';
						break; }
					echo '<span class="codeslot-status ' . ( $s['active'] ? 'is-active' : 'is-inactive' ) . '">' . esc_html( $s['active'] ? 'Active' : 'Inactive' ) . '</span> <button type="button" role="switch" class="button codeslot-status-toggle" aria-checked="' . ( $s['active'] ? 'true' : 'false' ) . '" aria-label="' . esc_attr( 'Enable ' . $name ) . '" data-id="' . absint( $post->ID ) . '"' . disabled( $s['locked'] || 'once' === $s['location'], true, false ) . '>' . esc_html( $s['active'] ? 'Disable' : 'Enable' ) . '</button>';
					break;
				case 'id':
					echo absint( $post->ID );
					break;
				case 'author':
					echo esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) );
					break;
				case 'created':
					echo esc_html( get_the_date( 'Y-m-d H:i', $post ) );
					break;
				case 'updated':
					echo esc_html( get_the_modified_date( 'Y-m-d H:i', $post ) );
					break;
				case 'shortcode':
					echo 'shortcode' === $s['location'] ? '<code>[codeslot id="' . absint( $post->ID ) . '"]</code>' : '—';
					break;
				case 'note':
					echo esc_html( $s['description'] );
					break;
				case 'categories':
				case 'tags':
					echo esc_html( $s[ $key ] ? implode( ', ', $s[ $key ] ) : '—' );
					break;
				default:
					echo esc_html( str_replace( '_', ' ', (string) $s[ $key ] ) );
			}
			echo '</td>';
		}
		echo '</tr>';
	}
}
