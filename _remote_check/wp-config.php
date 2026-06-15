<?php
define( 'WP_CACHE', true );




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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', "u345187203_Sumit" );

/** Database username */
define( 'DB_USER', "u345187203_Sumitb" );

/** Database password */
define( 'DB_PASSWORD', "Sumit8979!" );

/** Database hostname */
define( 'DB_HOST', "localhost" );

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
define( 'AUTH_KEY',          'B+LxfC }vX3@Qujxuq!|;+)k<CW#l&gW]XYdK8z^|s19yZA[u/{l;+Q1Y`cH_L.o' );
define( 'SECURE_AUTH_KEY',   'm52DW6a5 0.b<^HOhxkGhgeH~R~/%( <dn&~1b/ wcfe7+Wt]9rCIN2Nda(z@Q2j' );
define( 'LOGGED_IN_KEY',     'esZN$q#kmrh`TbknG?Hoy.~QtfS?iI#-7QU[[/XK;p4mAA>!oqB~*jr-u?BbZ3aG' );
define( 'NONCE_KEY',         ',npaD[@K72X!#2|KOAc6M+&3>Ry!,`opl|oR-W:iDE7P/@`uw[nOtp$>oU9`)ib:' );
define( 'AUTH_SALT',         'yFw X$9cB$8Q-bblXPLn%2:o4F.%o*(c(#HhKV>}<)N{EG5JK`!eq4fb&U30sOHY' );
define( 'SECURE_AUTH_SALT',  'nS]LRscR@V(]Qz2*IO|6p9`!6^A,sJd=D`}C,cMK8.7i^$q{#|k~#kF{fCkNLS|^' );
define( 'LOGGED_IN_SALT',    'lJA>akL$9 pa8&V$Q];mzJ59+l#5gKdKh6T*3k)R@jI?Ix-vpzVzq`O{$2lp:D?u' );
define( 'NONCE_SALT',        '}CE; E+2cm2)oY<`%~/Kh1F*InymR&N8Yf!0-<$,n:)VIp6ncnjO6NN5icqEQU_8' );
define( 'WP_CACHE_KEY_SALT', '~+q[)^0d;xDbLx*P_<wGe;3& :Py>[E&tqlB4#aj{0,b0bzj[nBO+1#$@8@J+wg`' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '9c03ffdb9bd3c958fb44cb796004d5a8' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
