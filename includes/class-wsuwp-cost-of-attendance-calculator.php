<?php

class WSUWP_Cost_Of_Attendance_Calculator {
	/**
	 * @var WSUWP_Cost_Of_Attendance_Calculator
	 */
	private static $instance;

	/**
	 * @var string Slug for tracking the content type of annual cost of attendance.
	 */
	var $post_type_slug = 'coa-annual-data';

	/**
	 * A list of post meta keys associated with the annual cost of attendance.
	 *
	 * @var array
	 */
	var $post_meta_keys = array(
		'in_state' => array(
			'sanitize_callback' => 'WSUWP_Cost_Of_Attendance_Calculator::sanitize_estimates',
		),
		'out_of_state' => array(
			'sanitize_callback' => 'WSUWP_Cost_Of_Attendance_Calculator::sanitize_estimates',
		),
		'dependent' => array(
			'sanitize_callback' => 'WSUWP_Cost_Of_Attendance_Calculator::sanitize_lookup_tables',
		),
		'independent' => array(
			'sanitize_callback' => 'WSUWP_Cost_Of_Attendance_Calculator::sanitize_lookup_tables',
		),
		'with_dependents' => array(
			'sanitize_callback' => 'WSUWP_Cost_Of_Attendance_Calculator::sanitize_lookup_tables',
		),
	);

	/**
	 * A list of names and labels for cost estimate inputs.
	 *
	 * @var array
	 */
	public static $cost_estimate_inputs = array(
		'tuition' => 'Tuition and fees',
		'room' => 'Room and board charges',
		'books' => 'Books and supplies',
		'other' => 'Other expenses',
	);

	/**
	 * A list of names and labels for grant aid estimate inputs.
	 *
	 * @var array
	 */
	public static $grant_aid_inputs = array(
		'no_aid' => 'No aid',
		'aid_0' => '0',
		'aid_1_1000' => '1-1000',
		'aid_1001_2500' => '1001-2500',
		'aid_2501_5000' => '2501-5000',
		'aid_5001_7500' => '5001-7500',
		'aid_7501_10000' => '7501-10000',
		'aid_10001_12500' => '10001-12500',
		'aid_12501_15000' => '12501-15000',
		'aid_15001_20000' => '15001-20000',
		'aid_20001_30000' => '20001-30000',
		'aid_30001_40000' => '30001-40000',
		'aid_40000' => '40000+',
	);

	/**
	 * Maintain and return the one instance. Initiate hooks when called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWP_Cost_Of_Attendance_Calculator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_Cost_Of_Attendance_Calculator();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'enter_title_here', array( $this, 'enter_year_here' ) );
		add_filter( 'screen_layout_columns', array( $this, 'screen_layout_columns' ) );
		add_filter( 'get_user_option_screen_layout_' . $this->post_type_slug, array( $this, 'screen_layout' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . $this->post_type_slug, array( $this, 'save' ), 10, 2 );
		add_shortcode( 'wsu_coa_calculator', array( $this, 'display_wsu_coa_calculator' ) );
	}

	/**
	 * Register the cost of attendance post type.
	 *
	 * @since 0.0.1
	 */
	public function register_post_type() {
		$labels = array(
			'name' => 'Net Price Calculator',
			'singular_name' => 'Annual Data',
			'all_items' => 'All Annual Data',
			'add_new' => 'Add Annual Data',
			'add_new_item' => 'Add Annual Data',
			'edit_item' => 'Edit Annual Data',
			'new_item' => 'New Annual Data',
			'view_item' => 'View Annual Data',
			'search_items' => 'Search Annual Data',
			'not_found' => 'No annual data found',
			'not_found_in_trash' => 'No annual data found in trash',
		);

		$args = array(
			'labels' => $labels,
			'description' => 'Annual Data for a given year',
			'public' => false,
			'show_ui' => true,
			'hierarchical' => false,
			'menu_position' => 58,
			'menu_icon' => 'dashicons-chart-area',
			'supports' => array(
				'title',
			),
		);
		register_post_type( $this->post_type_slug, $args );
	}

	/**
	 * Register the meta keys used to store cost of attendance data.
	 *
	 * @since 0.0.1
	 */
	public function register_meta() {
		foreach ( $this->post_meta_keys as $key => $args ) {
			$args['single'] = true;
			$args['type'] = 'array';
			register_meta( 'post', $key, $args );
		}
	}

	/**
	 * Enqueue scripts and styles used in the admin.
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && get_current_screen()->id === $this->post_type_slug ) {
			wp_enqueue_style( 'coac-admin', plugins_url( '/css/admin.css', dirname( __FILE__ ) ) );
		}
	}

	/**
	 * Change the "Enter title here" text for the annual cost of attendance content type.
	 *
	 * @since 0.0.1
	 *
	 * @param string $title The placeholder text displayed in the title input field.
	 *
	 * @return string
	 */
	public function enter_year_here( $title ) {
		$screen = get_current_screen();

		if ( $this->post_type_slug === $screen->post_type ) {
			$title = 'Enter year here';
		}

		return $title;
	}

	/**
	 * Limit layout options to one column.
	 *
	 * @since 0.0.1
	 *
	 * @param array $columns The array of screen layout columns
	 *
	 * @return array
	 */
	public function screen_layout_columns( $columns ) {
		$columns[ $this->post_type_slug ] = 1;

		return $columns;
	}

	/**
	 * Set layout option to one column.
	 *
	 * @since 0.0.1
	 *
	 * @return int
	 */
	public function screen_layout() {
		return 1;
	}

	/**
	 * Add the meta boxes used to capture estimate and lookup table data.
	 *
	 * @since 0.0.1
	 *
	 * @param string $post_type
	 */
	public function add_meta_boxes( $post_type ) {
		if ( $this->post_type_slug !== $post_type ) {
			return;
		}

		add_meta_box(
			'coac-estimates',
			'Estimates',
			array( $this, 'display_estimates_meta_box' ),
			null,
			'normal',
			'high'
		);

		add_meta_box(
			'coac-lookup-table_dependent',
			'Lookup Table for Dependents',
			array( $this, 'display_dependent_lookup_table' ),
			null,
			'normal'
		);

		add_meta_box(
			'coac-lookup-table_independent',
			'Lookup Table for Independent',
			array( $this, 'display_independent_lookup_table' ),
			null,
			'normal'
		);

		add_meta_box(
			'coac-lookup-table_with-dependents',
			'Lookup Table for Independents with Dependents',
			array( $this, 'display_with_dependents_lookup_table' ),
			null,
			'normal'
		);
	}

	/**
	 * Capture the main set of estimate data.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function display_estimates_meta_box( $post ) {
		$in_state = get_post_meta( $post->ID, 'in_state', true );
		$out_of_state = get_post_meta( $post->ID, 'out_of_state', true );

		wp_nonce_field( 'save-annual-data', '_coac_nonce' );
		?>
		<div class="cost-and-aid-inputs">

			<div class="row">
				<div class="label"></div>
				<div class="residency">In-state</div>
				<div class="residency">Out-of-state</div>
			</div>

			<p><strong>Cost Estimates</strong></p>

			<?php foreach ( self::$cost_estimate_inputs as $key => $title ) { ?>
				<?php
					$in_state_value = ( isset( $in_state[ $key ] ) ) ? absint( $in_state[ $key ] ) : '';
					$out_of_state_value = ( isset( $out_of_state[ $key ] ) ) ? absint( $out_of_state[ $key ] ) : '';
				?>
				<div class="row">
					<div class="label">
						<?php echo esc_html( $title ); ?>
					</div>
					<div class="residency">
						<input type="number" name="in_state[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $in_state_value ); ?>" />
					</div>
					<div class="residency">
						<input type="number" name="out_of_state[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $out_of_state_value ); ?>" />
					</div>
				</div>
			<?php } ?>

			<p><strong>Grant Aid Estimates</strong></p>

			<?php foreach ( self::$grant_aid_inputs as $key => $title ) { ?>
				<?php
					$in_state_value = ( isset( $in_state[ $key ] ) ) ? absint( $in_state[ $key ] ) : '';
					$out_of_state_value = ( isset( $out_of_state[ $key ] ) ) ? absint( $out_of_state[ $key ] ) : '';
				?>
				<div class="row">
					<div class="label">
						<?php echo esc_html( $title ); ?>
					</div>
					<div class="residency">
						<input type="number" name="in_state[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $in_state_value ); ?>" />
					</div>
					<div class="residency">
						<input type="number" name="out_of_state[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $out_of_state_value ); ?>" />
					</div>
				</div>
			<?php } ?>

		</div>
		<?php
	}

	/**
	 * Capture the lookup table data for dependents.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function display_dependent_lookup_table( $post ) {
		$data = get_post_meta( $post->ID, 'dependent', true );
		?>
		<div class="lookup-table-inputs">
			<div class="row">
				<div><$30,000</div>
				<div>$30,000-39,999</div>
				<div>$40,000-49,999</div>
				<div>$50,000-59,999</div>
				<div>$60,000-69,999</div>
				<div>$70,000-79,999</div>
				<div>$80,000-89,999</div>
				<div>$90,000-99,999</div>
				<div>$99,999+</div>
			</div>

			<?php foreach ( range( 0, 13 ) as $row ) { ?>
				<div class="row">
				<?php foreach ( range( 0, 8 ) as $index ) { ?>
					<?php $value = ( isset( $data[ $row ][ $index ] ) ) ? absint( $data[ $row ][ $index ] ) : ''; ?>
					<div>
						<input type="number" name="dependent[<?php echo esc_attr( $row ); ?>][<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $value ); ?>" />
					</div>
				<?php } ?>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Capture the lookup table data for independents.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function display_independent_lookup_table( $post ) {
		$data = get_post_meta( $post->ID, 'independent', true );
		?>
		<div class="lookup-table-inputs">
			<div class="row">
				<div><$30,000</div>
				<div>$30,000-39,999</div>
				<div>$40,000-49,999</div>
				<div>$50,000-59,999</div>
				<div>$60,000-69,999</div>
				<div>$70,000-79,999</div>
				<div>$80,000-89,999</div>
				<div>$90,000-99,999</div>
				<div>$99,999+</div>
			</div>

			<?php foreach ( range( 0, 2 ) as $row ) { ?>
				<div class="row">
				<?php foreach ( range( 0, 8 ) as $index ) { ?>
					<?php $value = ( isset( $data[ $row ][ $index ] ) ) ? absint( $data[ $row ][ $index ] ) : ''; ?>
					<div>
						<input type="number" name="independent[<?php echo esc_attr( $row ); ?>][<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $value ); ?>" />
					</div>
				<?php } ?>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Capture the lookup table data for independents with dependents.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function display_with_dependents_lookup_table( $post ) {
		$data = get_post_meta( $post->ID, 'with_dependents', true );
		?>
		<div class="lookup-table-inputs">
			<div class="row">
				<div><$30,000</div>
				<div>$30,000-39,999</div>
				<div>$40,000-49,999</div>
				<div>$50,000-59,999</div>
				<div>$60,000-69,999</div>
				<div>$70,000-79,999</div>
				<div>$80,000-89,999</div>
				<div>$90,000-99,999</div>
				<div>$99,999+</div>
			</div>

			<?php foreach ( range( 0, 9 ) as $row ) { ?>
				<div class="row">
				<?php foreach ( range( 0, 8 ) as $index ) { ?>
					<?php $value = ( isset( $data[ $row ][ $index ] ) ) ? absint( $data[ $row ][ $index ] ) : ''; ?>
					<div>
						<input type="number" name="with_dependents[<?php echo esc_attr( $row ); ?>][<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $value ); ?>" />
					</div>
				<?php } ?>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Sanitize the estimate data.
	 *
	 * @param string $estimates The unsanitized array of estimates.
	 *
	 * @return string The sanitized array of estimates.
	 */
	public static function sanitize_estimates( $estimates ) {
		$keys = array_keys( array_merge(
			self::$cost_estimate_inputs,
			self::$grant_aid_inputs
		) );

		$sanitized_estimates = array();

		foreach ( $estimates as $key => $value ) {
			if ( in_array( $key, $keys, true ) ) {
				$sanitized_estimates[ $key ] = absint( $value );
			}
		}

		return $sanitized_estimates;
	}

	/**
	 * Sanitize the income lookup tables.
	 *
	 * @param string $lookup_table The unsanitized array of lookup tables.
	 *
	 * @return string The sanitized array of lookup tables.
	 */
	public static function sanitize_lookup_tables( $lookup_table ) {
		$sanitized_lookup_table = array();

		foreach ( $lookup_table as $key => $value ) {
			$sanitized_lookup_table[ $key ] = array_map( 'absint', $value );
		}

		return $sanitized_lookup_table;
	}

	/**
	 * Save additional data associated with the the annual cost of attendance content type.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( ! isset( $_POST['_coac_nonce'] ) || ! wp_verify_nonce( $_POST['_coac_nonce'], 'save-annual-data' ) ) {
			return;
		}

		$keys = get_registered_meta_keys( 'post' );

		foreach ( $this->post_meta_keys as $key => $meta ) {
			if ( isset( $_POST[ $key ] ) && isset( $keys[ $key ] ) && isset( $keys[ $key ]['sanitize_callback'] ) ) {
				// Each piece of meta is registered with sanitization.
				update_post_meta( $post_id, $key, $_POST[ $key ] );
			}
		}
	}

	/**
	 * Display the calculator form for browsing scholarships.
	 */
	public function display_wsu_coa_calculator() {
		wp_enqueue_style( 'coa-calculator', plugins_url( 'css/calculator.css', dirname( __FILE__ ) ), array( 'spine-theme' ) );
		wp_enqueue_script( 'coa-calculator', plugins_url( 'js/calculator.js', dirname( __FILE__ ) ), array( 'jquery' ), false, true );

		$annual_data = get_posts( array( 'posts_per_page' => 1, 'post_type' => $this->post_type_slug ) );
		$recent_year = $annual_data[0];

		$coac_data = array(
			'figuresFrom' => $recent_year->post_title,
			'inState' => wp_json_encode( get_post_meta( $recent_year->ID, 'in_state', true ) ),
			'outOfState' => wp_json_encode( get_post_meta( $recent_year->ID, 'out_of_state', true ) ),
			'dependent' => wp_json_encode( get_post_meta( $recent_year->ID, 'dependent', true ) ),
			'independent' => wp_json_encode( get_post_meta( $recent_year->ID, 'independent', true ) ),
			'withDependents' => wp_json_encode( get_post_meta( $recent_year->ID, 'with_dependents', true ) ),
		);

		//wp_localize_script( 'coa-calculator', 'annualData', $coac_data );

		ob_start();

		include_once( __DIR__ . '/form.html' );

		$html = ob_get_clean();

		return $html;
	}
}
