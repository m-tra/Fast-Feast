<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'fasteqmf_wp194' );

/** MySQL database username */
define( 'DB_USER', 'fasteqmf_wp194' );

/** MySQL database password */
define( 'DB_PASSWORD', '5p6!L(0oSx' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'kefyq00etrtvjz4ldzd2vnusguezfwub96t0t3tjb0ooa46poauyiasicxgo7nat' );
define( 'SECURE_AUTH_KEY',  'ikufzxtfchsvjtrpalpc76ss4laew3mx9yuz6ai39uwid06r1r0wqvxfibrgrha9' );
define( 'LOGGED_IN_KEY',    '2uyeau93smfdxnizd4ohg83fctr3jcltjlnbzb61meldwbxomftsea0sagqh730v' );
define( 'NONCE_KEY',        '5kd12mrfvz64tjd8nad8hbqcaow69yqmsrabwo8cwf6joz9ptc2jwikjgurvglup' );
define( 'AUTH_SALT',        'avhnhoqsqkmlnjijand3auvyqb41hdwlwuwx15mzow48cxnubdorftqdloy28ck5' );
define( 'SECURE_AUTH_SALT', 'wy4n0myvr5xuoj995immbmcy0dkjulqzykxbe1nyxs0wbyrnutiskvccgr4wwkhm' );
define( 'LOGGED_IN_SALT',   'eyhkayupbrxu58snpybcd33pnvaohhaw2e5971pgjux7evq3g5pwpi8agvy0kczg' );
define( 'NONCE_SALT',       'aiemobfyxxypeag53l8p88oslyswho7cj66lhcpy7po5mcght1wjtjluwptbixv4' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_16';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
