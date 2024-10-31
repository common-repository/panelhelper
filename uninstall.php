<?php
/**
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 * @package WordPress Plugin Template/Uninstall
 */

// If plugin is not being uninstalled, exit (do nothing).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
panelhelper_remove_cron_schedule();

function panelhelper_remove_cron_schedule() {
    wp_clear_scheduled_hook( 'update_order_status_event' );
    // Remove custom cron schedule if it's no longer needed elsewhere
    $schedules = wp_get_schedules();
    if (isset($schedules['everyminute'])) {
        unset($schedules['everyminute']);
    }
}

// Do something here if plugin is being uninstalled.
