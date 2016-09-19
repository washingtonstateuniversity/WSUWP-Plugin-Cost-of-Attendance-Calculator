<?php
/*
Plugin Name: WSUWP Cost of Attendance Calculator
Version: 0.0.1
Description: Provides estimated attendance price information to students.
Author: washingtonstateuniversity, philcable
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-Cost-of-Attendance-Calculator
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The core plugin class.
require dirname( __FILE__ ) . '/includes/class-wsuwp-cost-of-attendance-calculator.php';

add_action( 'after_setup_theme', 'WSUWP_Cost_Of_Attendance_Calculator' );
/**
 * Start things up.
 *
 * @return \WSUWP_Cost_Of_Attendance_Calculator
 */
function WSUWP_Cost_Of_Attendance_Calculator() {
	return WSUWP_Cost_Of_Attendance_Calculator::get_instance();
}
