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
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'manhnguyentien' );

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
define( 'AUTH_KEY',         'vjv;%DveO;Yxq# E3d$)t4io0]39vQsY(}_C-#9#8LRuO*[/<,T]~c`>_:. ,+3r' );
define( 'SECURE_AUTH_KEY',  'G;6-0#Y3|B+=7a:Sq{C#F16@r>Xlp6L0yvYI3)ujx-?Z4U44kIrO7vK2G,A/S1{*' );
define( 'LOGGED_IN_KEY',    'SesU.,?jN;{N948V2dj/3~;q6=0(xl/4|>7d%-&WR#uc Ra^:_o:B}Vh|W4L~p`C' );
define( 'NONCE_KEY',        'Hr>X7&^,lD}k)}N/V[Br|0yN7wI2i(p6Dr3AhCgCCFDR6I3wj4QcdcQ{}Q|*)1&}' );
define( 'AUTH_SALT',        'KX-K[&`hQ2bHRaK< UO5i`_]S{P?$>23L&9:VpRyml9wie5=MoF6IJgJU%y,ilE]' );
define( 'SECURE_AUTH_SALT', 'X},x5J)P1-;[h70(kqk8C{?EJ@-?L+m1!OOe@_Go8$z]~8{U6MPNgNUf!kl2Rw%2' );
define( 'LOGGED_IN_SALT',   'Z%evEpH7n<Vh_h0QTG:]!?|m?AcVV^p{vv@EL^TQ!wTuzD#q4y:x:LK5C%xF_y|]' );
define( 'NONCE_SALT',       'T/S,=B@nrqPveD/&N:|m^`f|E/%2b.ATOpZVRp4:?).v/`pJd)v/27XtVve.%[g ' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_admin';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Chỉ ghi vào log, không hiển thị trên màn hình


/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
