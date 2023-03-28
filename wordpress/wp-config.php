<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordPress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '5w:?L78z!C|1(Z/*5Ss,/7n8|cui1;ZLBaoR9E^ucLk:^d2yc|LY=kkDHy/CK.$v' );
define( 'SECURE_AUTH_KEY',  '~6~89=IT{$~R[=xkAxu$o8t/~@u>#qI6Jk1I1c`pfss-DDZOo[=]H=a8%,B%UA$a' );
define( 'LOGGED_IN_KEY',    'N,Nv@O+XIB)t I:<yG!bb[ m%&xe4ddM_v1gm`RT}}?t4z:8#)~&9BqqKSDH-z1I' );
define( 'NONCE_KEY',        'WMd5MG!&86MDo~<,5{5<eefLx(~c@c80J>+6ltco$r>6b?QIo~f>W 5D?ZUBj[(<' );
define( 'AUTH_SALT',        ')FCB9k)_QFi*KPb7:~5O.Yt(kZ0#10*)D7.P1z|I7W=Y2Lz_IVIY(U[H[=07gs`6' );
define( 'SECURE_AUTH_SALT', '+-FmlkXpQB<tBHx4hYK)Q:yFS_&01L)jiaC~ }t3|tWe$O5M%HS&R[gL|%4bE-mF' );
define( 'LOGGED_IN_SALT',   '6_WG:.F^^>(VXS0y%#gEE ^YnXd^OG4HyZe{{fv=_U=t6#23TI_:;q$)*|OP![T.' );
define( 'NONCE_SALT',       ':z8`*SHO2J|rz~t<&LXJprDGuJsEw(t3(2|XOvU[W;|VPn;(P3dp|eA-10hp>yX=' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
