<?php
/** WordPress administration and guarded mutation routes. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {
	public static function boot() {
		add_action( 'admin_menu', array( self::class, 'menus' ) );
		add_action( 'admin_post_codeslot_save', array( self::class, 'save' ) );
		add_action( 'add_meta_boxes', array( self::class, 'boxes' ) );
		add_action( 'save_post', array( self::class, 'save_post' ), 10, 2 );
		add_action( 'admin_init', array( self::class, 'term_hooks' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_notices', array( self::class, 'notices' ) );
	}

	public static function menus() {
		if ( ! Permissions::allowed() ) {
			return;
		}
		add_menu_page( 'CodeSlot', 'CodeSlot', 'manage_options', 'codeslot', array( self::class, 'page' ), 'dashicons-editor-code' );
		foreach ( array(
			'codeslot'          => 'All Snippets',
			'codeslot-new'      => 'Add New',
			'codeslot-global'   => 'Header, Body & Footer',
			'codeslot-transfer' => 'Import / Export',
			'codeslot-tools'    => 'Tools',
			'codeslot-settings' => 'Settings',
		) as $slug => $label ) {
			add_submenu_page( 'codeslot', $label, $label, 'manage_options', $slug, array( self::class, 'page' ) );
		}
		add_options_page( 'CodeSlot', 'CodeSlot', 'manage_options', 'codeslot-global', array( self::class, 'page' ) );
	}

	public static function boxes() {
		if ( ! Permissions::allowed() ) {
			return;
		}
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			if ( 'attachment' !== $type ) {
				add_meta_box( 'codeslot-slots', __( 'CodeSlot — Header, Body & Footer', 'codeslot' ), array( self::class, 'box' ), $type, 'normal', 'default', array( '__block_editor_compatible_meta_box' => true ) );
			}
		}
	}

	public static function box( $post ) {
		wp_nonce_field( 'codeslot_slots', 'codeslot_nonce' );
		echo '<p>' . esc_html__( 'Runs only on this item’s singular frontend URL. Saved code is used in previews; unsaved changes and autosaves are not executed.', 'codeslot' ) . '</p>';
		self::slot_fields( get_post_meta( $post->ID, '_codeslot_slots', true ), 'codeslot_slots' );
	}

	public static function slot_fields( $slots, $prefix ) {
		foreach ( array(
			'header' => 'Header Code',
			'body'   => 'Body Code',
			'footer' => 'Footer Code',
		) as $slot => $label ) {
			$code    = isset( $slots[ $slot ]['code'] ) ? $slots[ $slot ]['code'] : '';
			$enabled = ! empty( $slots[ $slot ]['enabled'] );
			$id      = $prefix . '_' . $slot;
			echo '<fieldset class="codeslot-slot"><legend><strong>' . esc_html( $label ) . '</strong></legend><p><label><input type="checkbox" name="' . esc_attr( $prefix . '[' . $slot . '][enabled]' ) . '" value="1" ' . checked( $enabled, true, false ) . '> ' . esc_html__( 'Enable', 'codeslot' ) . '</label></p>';
			echo '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label><textarea data-codeslot-mode="text/html" class="large-text code codeslot-code" rows="7" id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . '[' . $slot . '][code]' ) . '">' . esc_textarea( $code ) . '</textarea></fieldset>';
		}
	}

	public static function save_post( $id, $post ) {
		if ( ! Permissions::allowed() || ! current_user_can( 'edit_post', $id ) || ! $post || ! is_post_type_viewable( $post->post_type ) || wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( ! isset( $_POST['codeslot_nonce'], $_POST['codeslot_slots'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['codeslot_nonce'] ) ), 'codeslot_slots' ) ) {
			return;
		}
		update_post_meta( $id, '_codeslot_slots', wp_slash( Permissions::slots( wp_unslash( $_POST['codeslot_slots'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	public static function term_hooks() {
		foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( self::class, 'term_add' ) );
			add_action( $taxonomy . '_edit_form_fields', array( self::class, 'term_edit' ) );
			add_action( 'created_' . $taxonomy, array( self::class, 'save_term' ) );
			add_action( 'edited_' . $taxonomy, array( self::class, 'save_term' ) );
		}
	}

	public static function term_add() {
		if ( Permissions::allowed() ) {
			echo '<div class="form-field">';
			wp_nonce_field( 'codeslot_term', 'codeslot_term_nonce' );
			self::slot_fields( array(), 'codeslot_slots' );
			echo '</div>';
		}
	}

	public static function term_edit( $term ) {
		if ( Permissions::allowed() ) {
			echo '<tr class="form-field"><th scope="row">CodeSlot</th><td>';
			wp_nonce_field( 'codeslot_term', 'codeslot_term_nonce' );
			self::slot_fields( get_term_meta( $term->term_id, '_codeslot_slots', true ), 'codeslot_slots' );
			echo '</td></tr>';
		}
	}

	public static function save_term( $id ) {
		if ( Permissions::allowed() && current_user_can( 'edit_term', $id ) && isset( $_POST['codeslot_term_nonce'], $_POST['codeslot_slots'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['codeslot_term_nonce'] ) ), 'codeslot_term' ) ) {
			update_term_meta( $id, '_codeslot_slots', wp_slash( Permissions::slots( wp_unslash( $_POST['codeslot_slots'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	}

	public static function assets() {
		$screen = get_current_screen();
		if ( ! Permissions::allowed() || ! $screen || ( false === strpos( $screen->id, 'codeslot' ) && ! ( 'post' === $screen->base && is_post_type_viewable( $screen->post_type ) ) && ! in_array( $screen->base, array( 'term', 'edit-tags' ), true ) ) ) {
			return;
		}
		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		wp_enqueue_script( 'codeslot-admin', plugins_url( 'assets/admin.js', CODESLOT_FILE ), array( 'code-editor' ), CODESLOT_VERSION, true );
		// Content-based cache key also refreshes styles when reinstalling version 1.0.0.
		$style_version = CODESLOT_VERSION . '-' . substr( hash_file( 'sha256', dirname( CODESLOT_FILE ) . '/assets/admin.css' ), 0, 12 );
		wp_enqueue_style( 'codeslot-admin', plugins_url( 'assets/admin.css', CODESLOT_FILE ), array(), $style_version );
	}

	public static function notices() {
		if ( ! Permissions::allowed() ) {
			return;
		}
		if ( Permissions::safe() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'CodeSlot Safe Mode is active. All CodeSlot output and snippet execution is disabled.', 'codeslot' ) . '</p></div>';
		}
		if ( 'missing' === get_option( 'codeslot_body_' . get_stylesheet() ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'CodeSlot observed that this theme omitted wp_body_open(). Body code will not run. Ask the theme author to add the hook; CodeSlot will not move your code.', 'codeslot' ) . '</p></div>';
		}
		if ( isset( $_GET['codeslot_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'CodeSlot changes saved.', 'codeslot' ) . '</p></div>';
		}
	}

	private static function form_start( $task, $id = 0 ) {
		echo '<form class="codeslot-panel" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="codeslot_save"><input type="hidden" name="task" value="' . esc_attr( $task ) . '"><input type="hidden" name="id" value="' . absint( $id ) . '">';
		wp_nonce_field( 'codeslot_save' );
	}

	public static function page() {
		if ( ! Permissions::allowed() ) {
			wp_die( esc_html__( 'Code access denied.', 'codeslot' ), '', array( 'response' => 403 ) );
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'codeslot'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap codeslot-wrap"><header class="codeslot-header"><div><h1>CodeSlot</h1><p>Manage your site code, all in one place.</p></div><span class="codeslot-version">' . esc_html( CODESLOT_VERSION ) . '</span></header>';
		echo '<nav class="codeslot-nav" aria-label="CodeSlot pages">';
		foreach ( array(
			'codeslot'          => 'All Snippets',
			'codeslot-new'      => 'Add New',
			'codeslot-global'   => 'Header, Body & Footer',
			'codeslot-transfer' => 'Import / Export',
			'codeslot-tools'    => 'Tools',
			'codeslot-settings' => 'Settings',
		) as $slug => $label ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '"' . ( $page === $slug ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		echo '</nav><div class="codeslot-content">';
		switch ( $page ) {
			case 'codeslot-global':
				echo '<h2>' . esc_html__( 'Global Header, Body & Footer', 'codeslot' ) . '</h2><p>' . esc_html__( 'Trusted raw code is inserted on frontend HTML pages. Output order: global → conditional snippets → individual code.', 'codeslot' ) . '</p>';
				self::form_start( 'global' );
				self::slot_fields( get_option( 'codeslot_global', array() ), 'slots' );
				submit_button();
				echo '</form>';
				break;
			case 'codeslot-new':
				self::editor();
				break;
			case 'codeslot-transfer':
				echo '<h2>Import / Export</h2><p class="codeslot-intro">' . esc_html__( 'Imports create inactive, unlocked copies. Review every line before activation. Maximum 5 MiB / 200 snippets.', 'codeslot' ) . '</p>';
				self::form_start( 'export' );
				echo '<h3>Export your snippets</h3><p>Download a JSON backup of your snippet library. Exports can contain private executable code; store them securely.</p>';
				submit_button( __( 'Download JSON', 'codeslot' ) );
				echo '</form>';
				self::form_start( 'import' );
				echo '<h3>Import snippets</h3><label for="codeslot-json">Paste CodeSlot JSON</label><textarea id="codeslot-json" name="json" rows="12" class="large-text code" required></textarea>';
				submit_button( __( 'Import inactive copies', 'codeslot' ) );
				echo '</form>';
				break;
			case 'codeslot-settings':
				$settings = get_option( 'codeslot_settings', array() );
				self::form_start( 'settings' );
				echo '<h2>Settings</h2><p class="codeslot-intro">Control execution and choose what happens to your data when CodeSlot is removed.</p><h3>Execution</h3><p><label><input type="checkbox" name="safe_mode" value="1" ' . checked( ! empty( $settings['safe_mode'] ), true, false ) . '> Safe Mode: disable all output and execution</label></p><h3>Uninstall preferences</h3><p>Code is retained by default. Back up your snippets before enabling deletion.</p><p><label><input type="checkbox" name="delete_data" value="1" ' . checked( ! empty( $settings['delete_data'] ), true, false ) . '> Delete CodeSlot data on uninstall (irreversible)</label></p>';
				submit_button();
				echo '</form>';
				break;
			case 'codeslot-tools':
				self::tools();
				break;
			default:
				self::listing();
		}
		echo '</div></div>';
	}

	private static function editor() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$s  = $id ? Repository::get( $id ) : array(
			'name'        => '',
			'description' => '',
			'code'        => '',
			'type'        => 'html',
			'location'    => 'header',
			'priority'    => 10,
			'active'      => false,
			'locked'      => false,
			'categories'  => array(),
			'tags'        => array(),
			'conditions'  => array(),
		);
		if ( ! $s ) {
			echo '<p>Snippet not found.</p>';
			return;
		}
		if ( ! $id && isset( $_GET['template'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$library = require dirname( CODESLOT_FILE ) . '/templates/library.php';
			$key     = sanitize_key( $_GET['template'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $library[ $key ] ) ) {
				$s = array_merge( $s, $library[ $key ] ); }
		}
		echo '<h2>' . esc_html( $id ? __( 'Edit Snippet', 'codeslot' ) : __( 'Add Snippet', 'codeslot' ) ) . '</h2><p class="codeslot-risk">' . esc_html__( 'PHP and scripts have full site privileges. This is not a sandbox. Keep a backup and filesystem access. PHP starts after query setup and cannot reliably register earlier hooks.', 'codeslot' ) . '</p>';
		self::form_start( 'snippet', $id );
		self::input( 'name', 'Snippet name', $s['name'] );
		self::input( 'description', 'Description', $s['description'] );
		echo '<div class="codeslot-editor-options"><p><label for="codeslot-type">Code type</label> <select id="codeslot-type" name="type">';
		foreach ( Repository::types() as $type ) {
			echo '<option ' . selected( $s['type'], $type, false ) . '>' . esc_html( $type ) . '</option>'; }
		echo '</select></p><p><label for="codeslot-location">Location</label> <select id="codeslot-location" name="location">';
		foreach ( array_unique( array_merge( Repository::locations( 'php' ), Repository::locations( 'html' ) ) ) as $location ) {
			echo '<option ' . selected( $s['location'], $location, false ) . '>' . esc_html( $location ) . '</option>'; }
		echo '</select></p></div><label for="codeslot-source">Code (PHP without tags; JS/CSS without wrappers)</label><textarea id="codeslot-source" name="code" rows="18" class="large-text code codeslot-code" data-codeslot-mode="' . esc_attr( self::mode( $s['type'] ) ) . '">' . esc_textarea( $s['code'] ) . '</textarea>';
		self::input( 'priority', 'Priority (lower runs first)', $s['priority'], 'number' );
		self::input( 'categories', 'Categories (comma separated)', implode( ',', $s['categories'] ) );
		self::input( 'tags', 'Tags (comma separated)', implode( ',', $s['tags'] ) );
		echo '<h3>Conditions</h3><p>OR between groups; AND within a group. Exclusions always win. Empty inclusion means all matching locations. Singular IDs match pages/posts/products; terms use taxonomy:ID. URL uses exact site-relative path. Mobile detection is advisory and requires cache variation.</p>';
		echo '<div id="codeslot-conditions"></div><input id="codeslot-conditions-json" type="hidden" name="conditions" value="' . esc_attr( wp_json_encode( $s['conditions'] ) ) . '">';
		echo '<p><label><input type="checkbox" name="active" value="1" ' . checked( $s['active'], true, false ) . '> Active</label> <label><input type="checkbox" name="locked" value="1" ' . checked( $s['locked'], true, false ) . '> Lock against changes</label></p>';
		if ( $s['locked'] ) {
			echo '<p><label><input type="checkbox" name="unlock" value="1"> Explicitly unlock and save</label></p>'; }
		submit_button( __( 'Save Snippet', 'codeslot' ) );
		echo '</form>';
		if ( $id ) {
			echo '<p>Usage: <code>[codeslot id="' . absint( $id ) . '"]</code> (requires shortcode location) or <code>do_action( \'codeslot_manual\', \'name\' );</code> (manual location). Execution status: ' . esc_html( $s['active'] ? 'active' : 'inactive' ) . '.</p>';
			$error = get_post_meta( $id, '_codeslot_error', true );
			if ( $error ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $error['date'] . ': ' . $error['message'] ) . '</p></div>'; }
			$history = get_post_meta( $id, '_codeslot_history', true );
			if ( is_array( $history ) ) {
				echo '<h3>Revision history (last 30 saved versions)</h3>';
				foreach ( array_reverse( $history, true ) as $index => $revision ) {
					self::form_start( 'rollback', $id );
					echo '<input type="hidden" name="revision" value="' . absint( $index ) . '"><p>' . esc_html( $revision['date'] . ' — ' . $revision['snippet']['name'] ) . '</p><details><summary>View code</summary><pre>' . esc_html( $revision['snippet']['code'] ) . '</pre></details>';
					submit_button( __( 'Restore as inactive', 'codeslot' ), 'secondary', 'submit', false );
					echo '</form>';
				}
			}
		}
	}

	private static function mode( $type ) {
		$modes = array(
			'php'        => 'text/x-php',
			'javascript' => 'text/javascript',
			'css'        => 'text/css',
			'text'       => 'text/plain',
		);
		return isset( $modes[ $type ] ) ? $modes[ $type ] : 'text/html';
	}

	private static function input( $name, $label, $value, $type = 'text' ) {
		echo '<p class="codeslot-field"><label for="codeslot-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label><br><input class="regular-text" id="codeslot-' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( 'name' === $name ? ' required aria-required="true"' : '' ) . '></p>';
	}

	private static function listing() {
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
		$status = in_array( $filter, array( 'draft', 'publish' ), true ) ? $filter : array( 'draft', 'publish' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order  = isset( $_GET['sort'] ) && 'title' === $_GET['sort'] ? 'title' : 'menu_order'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args   = array(
			'post_type'      => 'codeslot_snippet',
			'post_status'    => $status,
			'posts_per_page' => 30,
			'paged'          => $paged,
			's'              => $search,
			'orderby'        => array(
				$order => 'ASC',
				'ID'   => 'ASC',
			),
		);
		if ( ! empty( $_GET['category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Admin-only paginated filter.
				array(
					'taxonomy' => 'codeslot_category',
					'field'    => 'slug',
					'terms'    => sanitize_title( wp_unslash( $_GET['category'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
				),
			); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.DB.SlowDBQuery
		}
		$query = new \WP_Query( $args );
		echo '<div class="codeslot-section-heading"><div><h2>All Snippets</h2><p>Create, organize and edit your site code.</p></div><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=codeslot-new' ) ) . '">Add New</a></div>';
		$category = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		echo '<form class="codeslot-filters" method="get"><input type="hidden" name="page" value="codeslot"><label>Search<input name="s" placeholder="Search snippets" value="' . esc_attr( $search ) . '"></label><label>Status<select name="status"><option value="">All statuses</option><option value="publish" ' . selected( $filter, 'publish', false ) . '>Active</option><option value="draft" ' . selected( $filter, 'draft', false ) . '>Inactive</option></select></label><label>Sort<select name="sort"><option value="priority" ' . selected( $order, 'menu_order', false ) . '>Priority</option><option value="title" ' . selected( $order, 'title', false ) . '>Name</option></select></label><label>Category slug<input name="category" placeholder="All categories" value="' . esc_attr( $category ) . '"></label><button class="button" type="submit">Filter</button><a class="button" href="' . esc_url( admin_url( 'admin.php?page=codeslot' ) ) . '">Reset</a></form>';
		self::form_start( 'bulk' );
		echo '<div class="codeslot-bulk"><label>Bulk action<select name="bulk"><option value="deactivate">Deactivate</option><option value="activate">Activate</option><option value="duplicate">Duplicate inactive</option><option value="delete">Delete permanently</option></select></label><label class="codeslot-confirm"><input type="checkbox" name="confirm" value="1"> Confirm changes to selected snippets</label><button class="button" type="submit">Apply</button></div><table class="wp-list-table widefat striped codeslot-table"><caption class="screen-reader-text">Saved snippets and editing actions</caption><thead><tr><th scope="col">Select</th><th scope="col">Name</th><th scope="col">Type / Location</th><th scope="col">Status</th><th scope="col">Priority</th><th scope="col">Tags</th></tr></thead><tbody>';
		foreach ( $query->posts as $post ) {
			$s = Repository::get( $post->ID );
			if ( ! $s ) {
				continue; }
			/* translators: %d: ID of a saved snippet that has no name. */
			$name = '' !== trim( $s['name'] ) ? $s['name'] : sprintf( __( 'Untitled snippet #%d', 'codeslot' ), $post->ID );
			$edit = admin_url( 'admin.php?page=codeslot-new&id=' . $post->ID );
			echo '<tr><td data-label="Select"><input type="checkbox" aria-label="' . esc_attr( 'Select ' . $name ) . '" name="ids[]" value="' . absint( $post->ID ) . '"></td><td data-label="Name" class="codeslot-name"><strong><a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a></strong>' . ( $s['locked'] ? ' <span class="codeslot-lock">Locked</span>' : '' ) . '<p class="codeslot-description">' . esc_html( $s['description'] ) . '</p><a class="codeslot-edit" aria-label="' . esc_attr( 'Edit ' . $name ) . '" href="' . esc_url( $edit ) . '">Edit</a><span class="codeslot-id">ID: ' . absint( $post->ID ) . '</span></td><td data-label="Type / Location"><span class="codeslot-type">' . esc_html( $s['type'] ) . '</span><span class="codeslot-location">' . esc_html( str_replace( '_', ' ', $s['location'] ) ) . '</span></td><td data-label="Status"><span class="codeslot-status ' . ( $s['active'] ? 'is-active' : 'is-inactive' ) . '">' . esc_html( $s['active'] ? 'Active' : 'Inactive' ) . '</span></td><td data-label="Priority">' . (int) $s['priority'] . '</td><td data-label="Tags">' . esc_html( $s['tags'] ? implode( ', ', $s['tags'] ) : '—' ) . '</td></tr>';
		}
		if ( ! $query->post_count ) {
			echo '<tr><td colspan="6" class="codeslot-empty"><h3>No snippets found</h3><p>Add your first snippet or adjust the filters above.</p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=codeslot-new' ) ) . '">Add New</a></td></tr>';
		}
		echo '</tbody></table></form><nav class="codeslot-pagination" aria-label="Snippet pages">';
		for ( $i = 1; $i <= $query->max_num_pages; ++$i ) {
			echo '<a class="button"' . ( $i === $paged ? ' aria-current="page"' : '' ) . ' href="' . esc_url( add_query_arg( 'paged', $i ) ) . '">' . absint( $i ) . '</a> '; }
		echo '</nav>';
	}

	private static function tools() {
		echo '<section class="codeslot-panel"><h2>Recovery</h2><p>Set <code>define( \'CODESLOT_SAFE_MODE\', true );</code> in wp-config.php before WordPress loads. This disables every slot and snippet. If necessary rename the plugin directory. Safe Mode cannot undo code side effects.</p></section><section class="codeslot-panel"><h2>Local templates</h2><p>Start with a template, review its code and save it before activating.</p><div class="codeslot-templates">';
		$library = require dirname( CODESLOT_FILE ) . '/templates/library.php';
		foreach ( $library as $key => $template ) {
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=codeslot-new&template=' . $key ) ) . '">' . esc_html( $template['name'] ) . '</a></p>'; }
		echo '</div><p>Templates are inserted inactive for review. CodeSlot has no cloud dependencies or telemetry.</p></section>';
	}

	public static function save() {
		Permissions::verify();
		check_admin_referer( 'codeslot_save' ); // Explicit at the mutation route for static analyzers.
		$task   = isset( $_POST['task'] ) ? sanitize_key( $_POST['task'] ) : '';
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$page   = 'codeslot';
		$result = true;
		switch ( $task ) {
			case 'global':
				update_option( 'codeslot_global', Permissions::slots( isset( $_POST['slots'] ) ? wp_unslash( $_POST['slots'] ) : array() ), false ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$page = 'codeslot-global';
				break;
			case 'settings':
				update_option(
					'codeslot_settings',
					array(
						'safe_mode'   => ! empty( $_POST['safe_mode'] ),
						'delete_data' => ! empty( $_POST['delete_data'] ),
					),
					false
				);
				$page = 'codeslot-settings';
				break;
			case 'snippet':
				$data               = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$data['conditions'] = isset( $data['conditions'] ) ? json_decode( $data['conditions'], true, 32 ) : array();
				$result             = Repository::save( $data, $id, ! empty( $_POST['unlock'] ) );
				if ( ! is_wp_error( $result ) ) {
					$id = $result; }
				$page = 'codeslot-new';
				break;
			case 'rollback':
				$s       = Repository::get( $id );
				$history = get_post_meta( $id, '_codeslot_history', true );
				$index   = isset( $_POST['revision'] ) ? absint( $_POST['revision'] ) : -1;
				if ( ! $s || ! is_array( $history ) || ! isset( $history[ $index ] ) ) {
					$result = new \WP_Error( 'revision', 'Revision not found.' );
					break; }
				$data           = $history[ $index ]['snippet'];
				$data['active'] = false;
				$data['locked'] = false;
				$result         = Repository::save( $data, $id );
				$page           = 'codeslot-new';
				break;
			case 'bulk':
				if ( empty( $_POST['confirm'] ) || empty( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) || count( $_POST['ids'] ) > 100 ) {
					$result = new \WP_Error( 'confirm', 'Select 1–100 snippets and confirm the operation.' );
					break; }
				$action = isset( $_POST['bulk'] ) ? sanitize_key( $_POST['bulk'] ) : '';
				foreach ( array_unique( array_map( 'absint', $_POST['ids'] ) ) as $target ) {
					$s = Repository::get( $target );
					if ( ! $s || $s['locked'] ) {
						$result = new \WP_Error( 'locked', 'Missing or locked snippet; previous eligible changes may have been applied.' );
						break; }
					if ( 'delete' === $action ) {
						wp_delete_post( $target, true );
						continue; }
					if ( 'duplicate' === $action ) {
						$s['name']  .= ' (copy)';
						$s['active'] = false;
						$result      = Repository::save( $s ); } elseif ( in_array( $action, array( 'activate', 'deactivate' ), true ) ) {
						$s['active'] = 'activate' === $action;
						$result      = Repository::save( $s, $target ); } else {
							$result = new \WP_Error( 'action', 'Unknown bulk action.' ); }
						if ( is_wp_error( $result ) ) {
							break; }
				}
				Runtime::clear_cache();
				break;
			case 'import':
				$result = Repository::import( isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Bounded schema validation in Repository::import; source must remain intact.
				$page   = 'codeslot-transfer';
				break; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			case 'export':
				nocache_headers();
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="codeslot-export.json"' );
				echo wp_json_encode( Repository::export(), JSON_PRETTY_PRINT );
				exit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			default:
				$result = new \WP_Error( 'task', 'Unknown operation.' );
		}
		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html( $result->get_error_message() ),
				'CodeSlot',
				array(
					'response'  => 400,
					'back_link' => true,
				)
			); }
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => $page,
					'id'             => $id,
					'codeslot_saved' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
