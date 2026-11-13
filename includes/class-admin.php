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
			'codeslot-library'  => 'Library',
			'codeslot-files'    => 'File Editor',
			'codeslot-pixels'   => 'Conversion Pixels',
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
		echo '<p>' . esc_html__( 'Free Header, Body and Footer code for this individual item only. Runs on its singular frontend URL. Saved code is used in previews; unsaved changes and autosaves are not executed.', 'codeslot' ) . '</p>';
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
			echo '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label><textarea data-codeslot-mode="text/html" class="large-text code codeslot-code" rows="7" id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . '[' . $slot . '][code]' ) . '">' . esc_textarea( $code ) . '</textarea><p class="description">' . esc_html(
				array(
					'header' => 'This code is printed inside the <head> section.',
					'body'   => 'This code is printed just after the opening <body> tag when the theme provides wp_body_open().',
					'footer' => 'This code is printed before the closing </body> tag when the theme provides wp_footer().',
				)[ $slot ]
			) . '</p></fieldset>';
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
			echo '<div class="form-field"><h3>CodeSlot — Header, Body &amp; Footer</h3><p>Free individual code for this term archive only. Enable each slot separately.</p>';
			wp_nonce_field( 'codeslot_term', 'codeslot_term_nonce' );
			self::slot_fields( array(), 'codeslot_slots' );
			echo '</div>';
		}
	}

	public static function term_edit( $term ) {
		if ( Permissions::allowed() ) {
			echo '<tr class="form-field"><th scope="row">CodeSlot</th><td><p>Free Header, Body and Footer code for this term archive only. Enable each slot separately.</p>';
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
		$script_version = CODESLOT_VERSION . '-' . substr( hash_file( 'sha256', dirname( CODESLOT_FILE ) . '/assets/admin.js' ), 0, 12 );
		wp_enqueue_script( 'codeslot-admin', plugins_url( 'assets/admin.js', CODESLOT_FILE ), array( 'code-editor' ), $script_version, true );
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
		$restore = get_transient( 'codeslot_restore_' . get_current_user_id() );
		if ( is_array( $restore ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( 'Backup restored with Safe Mode on. Individual matches: ' . $restore['mapped'] . '; missing/unsupported matches skipped: ' . $restore['skipped'] . '. Review and enable each section deliberately.' ) . '</p></div>';
			delete_transient( 'codeslot_restore_' . get_current_user_id() );
		}
		if ( isset( $_GET['codeslot_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'CodeSlot changes saved.', 'codeslot' ) . '</p></div>';
		}
	}

	private static function form_start( $task, $id = 0, $form_id = '' ) {
		echo '<form' . ( $form_id ? ' id="' . esc_attr( $form_id ) . '"' : '' ) . ' class="codeslot-panel" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="codeslot_save"><input type="hidden" name="task" value="' . esc_attr( $task ) . '"><input type="hidden" name="id" value="' . absint( $id ) . '">';
		wp_nonce_field( 'codeslot_save' );
	}

	public static function page() {
		if ( ! Permissions::allowed() ) {
			wp_die( esc_html__( 'Code access denied.', 'codeslot' ), '', array( 'response' => 403 ) );
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'codeslot'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap codeslot-wrap"><header class="codeslot-header"><div class="codeslot-brand"><span class="codeslot-mark" aria-hidden="true">&lt;/&gt;</span><div><h1>CodeSlot</h1><p>Global & individual code slots. All features free.</p></div></div><span class="codeslot-version">' . esc_html( CODESLOT_VERSION ) . '</span></header>';
		echo '<nav class="codeslot-nav" aria-label="CodeSlot pages">';
		foreach ( array(
			'codeslot'          => 'All Snippets',
			'codeslot-new'      => 'Add New',
			'codeslot-global'   => 'Header, Body & Footer',
			'codeslot-transfer' => 'Import / Export',
			'codeslot-library'  => 'Library',
			'codeslot-files'    => 'File Editor',
			'codeslot-pixels'   => 'Conversion Pixels',
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
				Backup::page();
				break;
			case 'codeslot-settings':
				$settings = get_option( 'codeslot_settings', array() );
				self::form_start( 'settings' );
				echo '<h2>Settings</h2><p class="codeslot-intro">Control execution and choose what happens to your data when CodeSlot is removed.</p><h3>Execution</h3><p><label><input type="checkbox" name="safe_mode" value="1" ' . checked( ! empty( $settings['safe_mode'] ), true, false ) . '> Safe Mode: disable all output and execution</label></p><h3>Uninstall preferences</h3><p>Code is retained by default. Back up your snippets before enabling deletion.</p><p><label><input type="checkbox" name="delete_data" value="1" ' . checked( ! empty( $settings['delete_data'] ), true, false ) . '> Delete CodeSlot data on uninstall (irreversible)</label></p>';
				submit_button();
				echo '</form>';
				break;
			case 'codeslot-files':
				Files::page();
				break;
			case 'codeslot-pixels':
				Pixels::page();
				break;
			case 'codeslot-library':
				Library::page();
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
			if ( isset( $library[ $key ] ) && empty( $library[ $key ]['manual'] ) ) {
				$s = array_merge( $s, $library[ $key ] ); }
		}
		echo '<h2>' . esc_html( $id ? __( 'Edit Snippet', 'codeslot' ) : __( 'Add Snippet', 'codeslot' ) ) . '</h2><p class="codeslot-risk">' . esc_html__( 'PHP and scripts have full site privileges. This is not a sandbox. Keep a backup and filesystem access. PHP normally starts after query setup. The optional bootstrap location runs at init on all request types, including login, AJAX, REST, cron and CLI, without query conditions. Earlier plugin-load constants still belong in wp-config.php.', 'codeslot' ) . '</p>';
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
		echo '<div class="codeslot-metadata">';
		echo '<div id="codeslot-paragraph-field" hidden>';
		self::input( 'paragraph', 'Paragraph number (1–1000)', isset( $s['paragraph'] ) ? $s['paragraph'] : 1, 'number' );
		echo '</div>';
		self::input( 'priority', 'Priority (lower runs first)', $s['priority'], 'number' );
		self::input( 'categories', 'Categories (comma separated)', implode( ',', $s['categories'] ) );
		self::input( 'tags', 'Tags (comma separated)', implode( ',', $s['tags'] ) );
		echo '</div>';
		echo '<h3>Conditions</h3><p>OR between groups; AND within a group. Exclusions always win. Empty inclusion means all matching locations. Singular IDs match pages/posts/products; terms use taxonomy:ID. URL uses exact site-relative path. Mobile detection is advisory and requires cache variation.</p>';
		echo '<div id="codeslot-conditions"></div><input id="codeslot-conditions-json" type="hidden" name="conditions" value="' . esc_attr( wp_json_encode( $s['conditions'] ) ) . '">';
		echo '<p><label><input type="checkbox" name="active" value="1" ' . checked( $s['active'], true, false ) . '> Active</label> <label><input type="checkbox" name="locked" value="1" ' . checked( $s['locked'], true, false ) . '> Lock against changes</label></p><p class="description">Lock prevents accidental edits, status changes and deletion. It does not stop an active snippet. Use the full editor to explicitly unlock it.</p>';
		if ( $s['locked'] ) {
			echo '<p><label><input type="checkbox" name="unlock" value="1"> Explicitly unlock and save</label></p>'; }
		submit_button( __( 'Save Snippet', 'codeslot' ) );
		echo '</form>';
		if ( $id ) {
			echo '<p>Usage: <code>[codeslot id="' . absint( $id ) . '"]</code> (requires shortcode location) or <code>do_action( \'codeslot_manual\', \'name\' );</code> (manual location). Execution status: ' . esc_html( $s['active'] ? 'active' : 'inactive' ) . '.</p>';
			if ( 'php' === $s['type'] && 'once' === $s['location'] ) {
				$record = Jobs::record( $id );
				echo '<section class="codeslot-panel"><h3>Run Once PHP</h3><p>This privileged task can change your database or files. Back up first. It remains inactive and never runs automatically. A claimed task is never automatically retried; duplicate it only if you deliberately need a new task.</p>';
				if ( $record ) {
					echo '<p>Status: <strong>' . esc_html( $record['status'] ) . '</strong></p><pre>' . esc_html( wp_json_encode( $record, JSON_PRETTY_PRINT ) ) . '</pre>';
				} else {
					self::form_start( 'run_once', $id );
					echo '<label><input type="checkbox" name="confirm" value="1" required> I backed up the site and confirm running this PHP task once.</label>';
					submit_button( 'Run Once', 'secondary' );
					echo '</form>';
				}
				echo '</section>';
			}
			$error = get_post_meta( $id, '_codeslot_error', true );
			if ( $error ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $error['date'] . ': ' . $error['message'] ) . '</p></div>'; }
			$history = get_post_meta( $id, '_codeslot_history', true );
			if ( is_array( $history ) ) {
				echo '<h3>Revision history (last 30 saved versions)</h3>';
				if ( $history ) {
					self::form_start( 'clear_history', $id );
					echo '<button class="button codeslot-clear-history" type="submit">Clear revision history</button><p>Deletes saved versions permanently. Current code is retained; deleted versions cannot be recovered.</p></form>';
				}
				foreach ( array_reverse( $history, true ) as $index => $revision ) {
					self::form_start( 'rollback', $id );
					echo '<input type="hidden" name="revision" value="' . absint( $index ) . '"><p>' . esc_html( $revision['date'] . ' — ' . $revision['snippet']['name'] ) . '</p><details><summary>View code</summary><pre>' . esc_html( $revision['snippet']['code'] ) . '</pre></details>';
					echo '<details class="codeslot-revision-diff"><summary>Compare saved code with current code</summary><div class="codeslot-diff-scroll">';
					$diff = wp_text_diff(
						$revision['snippet']['code'],
						$s['code'],
						array(
							'title_left'  => 'Saved version',
							'title_right' => 'Current code',
						)
					);
					echo $diff ? wp_kses_post( $diff ) : '<p>No code changes.</p>';
					echo '</div></details>';
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
		echo '<p class="codeslot-field"><label for="codeslot-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label><br><input class="regular-text" id="codeslot-' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( in_array( $name, array( 'name', 'quick_name' ), true ) ? ' required aria-required="true"' : '' ) . '></p>';
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
		echo '<section id="codeslot-quick-edit" class="codeslot-quick-panel" hidden><h3>Quick Edit</h3>';
		self::form_start( 'quick_edit', 0, 'codeslot-quick-form' );
		self::input( 'quick_name', 'Snippet name', '' );
		self::input( 'quick_description', 'Description', '' );
		echo '<div class="codeslot-metadata">';
		self::input( 'quick_priority', 'Priority', 10, 'number' );
		self::input( 'quick_categories', 'Categories (comma separated)', '' );
		self::input( 'quick_tags', 'Tags (comma separated)', '' );
		echo '</div><p><label><input type="checkbox" name="quick_active" value="1"> Active</label></p><p>Code, location and conditions are preserved. Use Edit for the full snippet editor.</p><div class="codeslot-actions"><button type="submit" class="button button-primary">Update Snippet</button><button type="button" class="button codeslot-cancel-quick">Cancel</button></div></form></section>';
		self::form_start( 'toggle', 0, 'codeslot-toggle-form' );
		echo '<input type="hidden" name="active" value="0"></form>';
		self::form_start( 'bulk', 0, 'codeslot-bulk-form' );
		echo '<div class="codeslot-bulk"><label class="codeslot-select-page"><input type="checkbox" id="codeslot-select-page"> Select page</label><label class="codeslot-bulk-action">Bulk action<select name="bulk"><option value="deactivate">Deactivate</option><option value="activate">Activate</option><option value="edit">Edit selected</option><option value="duplicate">Duplicate inactive</option><option value="delete">Delete permanently</option></select></label><button class="button" type="submit">Apply</button></div><fieldset class="codeslot-bulk-editor" hidden disabled><legend>Bulk Edit</legend><p>Only checked fields are changed. Code and conditions are preserved.</p><div class="codeslot-metadata"><label><input type="checkbox" name="bulk_priority_enabled" value="1"> Change priority<input type="number" name="bulk_priority" value="10" min="-1000" max="1000"></label><label>Status<select name="bulk_status"><option value="keep">No change</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label><label><input type="checkbox" name="bulk_categories_enabled" value="1"> Replace categories<input name="bulk_categories"></label><label><input type="checkbox" name="bulk_tags_enabled" value="1"> Replace tags<input name="bulk_tags"></label></div></fieldset><table class="wp-list-table widefat striped codeslot-table"><caption class="screen-reader-text">Saved snippets and editing actions</caption><thead><tr><th scope="col" class="check-column"><input type="checkbox" id="codeslot-select-all" aria-label="Select all snippets on this page"></th><th scope="col">Name</th><th scope="col">Type / Location</th><th scope="col">Status</th><th scope="col">Priority</th><th scope="col">Tags</th></tr></thead><tbody>';
		foreach ( $query->posts as $post ) {
			$s = Repository::get( $post->ID );
			if ( ! $s ) {
				continue; }
			/* translators: %d: ID of a saved snippet that has no name. */
			$name               = '' !== trim( $s['name'] ) ? $s['name'] : sprintf( __( 'Untitled snippet #%d', 'codeslot' ), $post->ID );
			$author             = get_the_author_meta( 'display_name', (int) $post->post_author );
			$edit               = admin_url( 'admin.php?page=codeslot-new&id=' . $post->ID );
			$quick_data         = $s;
			$quick_data['name'] = $name;
			$quick              = wp_json_encode( array_intersect_key( $quick_data, array_flip( array( 'id', 'name', 'description', 'priority', 'active', 'categories', 'tags', 'location' ) ) ) );
			echo '<tr><td class="check-column" data-label="Select" data-colname="Select"><input type="checkbox" aria-label="' . esc_attr( 'Select ' . $name ) . '" name="ids[]" value="' . absint( $post->ID ) . '"></td><td data-label="Name" data-colname="Name" class="codeslot-name"><strong><a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a></strong>' . ( $s['locked'] ? ' <span class="codeslot-lock">Locked</span>' : '' ) . '<p class="codeslot-description">' . esc_html( $s['description'] ) . '</p><a class="codeslot-edit" aria-label="' . esc_attr( 'Edit ' . $name ) . '" href="' . esc_url( $edit ) . '">Edit</a> <button type="button" class="button-link codeslot-quick" data-snippet="' . esc_attr( $quick ) . '" aria-controls="codeslot-quick-edit" aria-expanded="false"' . disabled( $s['locked'], true, false ) . '>Quick Edit</button> <button type="button" class="button-link codeslot-remove" data-id="' . absint( $post->ID ) . '" data-name="' . esc_attr( $name ) . '"' . disabled( $s['locked'], true, false ) . '>Delete</button><span class="codeslot-id">ID: ' . absint( $post->ID ) . '</span><p class="codeslot-description">' . esc_html( 'Author: ' . $author . ' · Created: ' . get_the_date( 'Y-m-d', $post ) . ' · Categories: ' . ( $s['categories'] ? implode( ', ', $s['categories'] ) : '—' ) ) . '</p></td><td data-label="Type / Location" data-colname="Type / Location"><span class="codeslot-type">' . esc_html( $s['type'] ) . '</span><span class="codeslot-location">' . esc_html( str_replace( '_', ' ', $s['location'] ) ) . '</span></td><td data-label="Status" data-colname="Status"><span class="codeslot-status ' . ( $s['active'] ? 'is-active' : 'is-inactive' ) . '">' . esc_html( $s['active'] ? 'Active' : 'Inactive' ) . '</span> <button type="button" role="switch" class="button codeslot-status-toggle" aria-checked="' . ( $s['active'] ? 'true' : 'false' ) . '" aria-label="' . esc_attr( 'Enable ' . $name ) . '" data-id="' . absint( $post->ID ) . '"' . disabled( $s['locked'] || 'once' === $s['location'], true, false ) . '>' . esc_html( $s['active'] ? 'Disable' : 'Enable' ) . '</button></td><td data-label="Priority" data-colname="Priority">' . (int) $s['priority'] . '</td><td data-label="Tags" data-colname="Tags">' . esc_html( $s['tags'] ? implode( ', ', $s['tags'] ) : '—' ) . '</td></tr>';
		}
		if ( ! $query->post_count ) {
			echo '<tr><td colspan="6" class="codeslot-empty"><h3>No snippets found</h3><p>Add your first snippet or adjust the filters above.</p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=codeslot-new' ) ) . '">Add New</a></td></tr>';
		}
		echo '</tbody></table></form>';
		self::form_start( 'delete', 0, 'codeslot-delete-form' );
		echo '<input type="hidden" name="confirm" value="0"></form>';
		$total = (int) $query->max_num_pages;
		echo '<nav class="codeslot-pagination tablenav" aria-label="Snippet pages"><span class="displaying-num">' . esc_html( number_format_i18n( $query->found_posts ) ) . ' items</span><span class="pagination-links">';
		foreach ( array(
			'first'    => 1,
			'previous' => max( 1, $paged - 1 ),
			'next'     => min( $total, $paged + 1 ),
			'last'     => $total,
		) as $label => $target ) {
			$disabled = ! $total || ( in_array( $label, array( 'first', 'previous' ), true ) ? $paged <= 1 : $paged >= $total );
			if ( $disabled ) {
				echo '<span class="button disabled" aria-disabled="true">' . esc_html( ucfirst( $label ) ) . '</span>';
			} else {
				echo '<a class="button" aria-label="' . esc_attr( ucfirst( $label ) . ' page' ) . '" href="' . esc_url( add_query_arg( 'paged', $target ) ) . '">' . esc_html( ucfirst( $label ) ) . '</a>';
			}
			if ( 'previous' === $label ) {
				echo '<span class="codeslot-page-count">' . esc_html( (string) ( $total ? $paged : 0 ) ) . ' / ' . esc_html( (string) $total ) . '</span>';
			}
		}
		echo '</span></nav>';
	}

	private static function tools() {
		Support::page();
		echo '<section class="codeslot-panel"><h2>Recovery</h2><p>Set <code>define( \'CODESLOT_SAFE_MODE\', true );</code> in wp-config.php before WordPress loads. Use Settings Safe Mode when the dashboard works. The wp-config.php constant is an emergency override when code blocks dashboard access; Settings cannot turn it off. Both suppress every CodeSlot slot/snippet and new pixel/virtual-file output. If necessary rename the plugin directory. Safe Mode cannot undo side effects, cached responses or registered service workers.</p></section><section class="codeslot-panel"><h2>Local templates</h2><p>Start with a template, review its code and save it before activating.</p><div class="codeslot-templates">';
		$library = require dirname( CODESLOT_FILE ) . '/templates/library.php';
		foreach ( array_slice( $library, 0, 4, true ) as $key => $template ) {
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=codeslot-new&template=' . $key ) ) . '">' . esc_html( $template['name'] ) . '</a></p>'; }
		echo '</div><p>These four starter examples open inactive. Find all bundled recipes and setup guides on the Library page. No account, cloud connection or extra WordPress plugin is required by the bundled library.</p></section>';
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
			case 'run_once':
				$page   = 'codeslot-new';
				$result = empty( $_POST['confirm'] ) ? new \WP_Error( 'confirm', 'Confirm this one-time task explicitly.' ) : Jobs::run( $id );
				if ( is_array( $result ) && 'completed' !== $result['status'] ) {
					$result = new \WP_Error( 'job_failed', 'Task failed. Return to the snippet to review its saved execution record.' );
				}
				break;
			case 'toggle':
				$s = Repository::get( $id );
				if ( ! $s || $s['locked'] || 'once' === $s['location'] ) {
					$result = new \WP_Error( 'locked', 'Unlock this snippet before changing status. Run Once tasks cannot be activated.' );
					break;
				}
				$s['active'] = ! empty( $_POST['active'] );
				$result      = Repository::save( $s, $id );
				break;
			case 'quick_edit':
				$s = Repository::get( $id );
				if ( ! $s || $s['locked'] ) {
					$result = new \WP_Error( 'locked', 'Missing or locked snippet. Use the full editor to unlock it.' );
					break;
				}
				$s['name'] = isset( $_POST['quick_name'] ) ? sanitize_text_field( wp_unslash( $_POST['quick_name'] ) ) : '';
				if ( '' === trim( $s['name'] ) ) {
					$result = new \WP_Error( 'name', 'A snippet name is required.' );
					break;
				}
				foreach ( array( 'description', 'categories', 'tags', 'priority' ) as $field ) {
					if ( isset( $_POST[ 'quick_' . $field ] ) && is_scalar( $_POST[ 'quick_' . $field ] ) ) {
						$s[ $field ] = sanitize_text_field( wp_unslash( $_POST[ 'quick_' . $field ] ) );
					}
				}
				$s['active'] = ! empty( $_POST['quick_active'] );
				$result      = Repository::save( $s, $id );
				break;
			case 'delete':
				$s = Repository::get( $id );
				if ( empty( $_POST['confirm'] ) || ! $s || $s['locked'] ) {
					$result = new \WP_Error( 'delete', 'Confirm deletion of an unlocked snippet.' );
					break;
				}
				$result = wp_delete_post( $id, true ) ? true : new \WP_Error( 'delete', 'Unable to delete the snippet.' );
				Runtime::clear_cache();
				break;
			case 'clear_history':
				$s = Repository::get( $id );
				if ( ! $s || $s['locked'] ) {
					$result = new \WP_Error( 'locked', 'Unlock this snippet before clearing history.' );
					break;
				}
				delete_post_meta( $id, '_codeslot_history' );
				foreach ( wp_get_post_revisions( $id ) as $revision ) {
					wp_delete_post_revision( $revision->ID );
				}
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
				if ( empty( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) || count( $_POST['ids'] ) > 100 ) {
					$result = new \WP_Error( 'confirm', 'Select 1–100 snippets before applying an action.' );
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
					if ( 'activate' === $action && 'once' === $s['location'] ) {
						$result = new \WP_Error( 'action', 'Run Once tasks stay inactive. Use their confirmed Run Once button.' );
						break;
					}
					if ( 'edit' === $action ) {
						$changed = false;
						foreach ( array( 'priority', 'categories', 'tags' ) as $field ) {
							if ( ! empty( $_POST[ 'bulk_' . $field . '_enabled' ] ) && isset( $_POST[ 'bulk_' . $field ] ) && is_scalar( $_POST[ 'bulk_' . $field ] ) ) {
								$s[ $field ] = sanitize_text_field( wp_unslash( $_POST[ 'bulk_' . $field ] ) );
								$changed     = true;
							}
						}
						if ( isset( $_POST['bulk_status'] ) && in_array( $_POST['bulk_status'], array( 'active', 'inactive' ), true ) ) {
							$s['active'] = 'active' === $_POST['bulk_status'];
							$changed     = true;
						}
						$result = $changed ? Repository::save( $s, $target ) : new \WP_Error( 'bulk', 'Choose at least one field to change.' );
					} elseif ( 'duplicate' === $action ) {
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
		Support::log( $task, $id );
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
