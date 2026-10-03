<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ho_wordpress_db' );

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
define( 'AUTH_KEY',         'UsAka^J@$cuIwz4$~WUH=nqeNb3DMinkdppar6x!sI~^?@/$~Doifo$mJfE}495(' );
define( 'SECURE_AUTH_KEY',  '.5,864kp6`TD1ds[SWPse K$o&uW>(q.c~-k5x+L7RR@q{;aM%(G$dZyD+BJ_juk' );
define( 'LOGGED_IN_KEY',    'FH[|1gM5f3[Pwn0dXt /Co1;k[&r+9Y%8AeVw.ZD}G3)igckvrm3d_4uyQlt+a5%' );
define( 'NONCE_KEY',        '}Qg9;Kqo7W(btOvUv!4pQRd4?fO3Mzzf#OFECB8w?: `qwl`u<`RCf<VjK/H=Wyl' );
define( 'AUTH_SALT',        'nyj~BV=N{r`e{YSDk6)@q7u|;PN%!bCbVk26U&&=wUzClQT>IJe{fsO``7%kwXCS' );
define( 'SECURE_AUTH_SALT', 'L_cq4LgjJ2u$[B%B/j?V-+%V8Trt8uNT#-Jj9F8UcRVP#_fi+:F|ZK&~y<~|%8#`' );
define( 'LOGGED_IN_SALT',   'Y=0@Hy,>qSD~6Z!R2+#$wt![MUNZ4Rl|j@2dI~W;9Igd+t~yFa1&0dJTVuL=v,-T' );
define( 'NONCE_SALT',       '4u*QW4P3{UL.ApF`iF&<*Da[TltT$dtp7K+C?:uZDl.0%xoLFvf75NST7Tt~z^75' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
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
