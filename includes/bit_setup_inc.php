<?php
use Bitweaver\Liberty\LibertyProtector;
global $gBitSystem, $gLibertySystem, $gProtector;

$pRegisterHash = [
	'package_name' => 'protector',
	'package_path' => dirname( dirname( __FILE__ ) ).'/',
	'service' => LIBERTY_SERVICE_ACCESS_CONTROL,
];

// fix to quieten down VS Code which can't see the dynamic creation of these ...
define( 'PROTECTOR_PKG_NAME', $pRegisterHash['package_name'] );
define( 'PROTECTOR_PKG_URL', BIT_ROOT_URL . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'PROTECTOR_PKG_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'PROTECTOR_PKG_INCLUDE_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/');
define( 'PROTECTOR_PKG_CLASS_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/classes/');
define( 'PROTECTOR_PKG_ADMIN_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/admin/');

$gBitSystem->registerPackage( $pRegisterHash );

if( $gBitSystem->isPackageActive( 'protector' ) ) {

	$gLibertySystem->registerService( LIBERTY_SERVICE_ACCESS_CONTROL, PROTECTOR_PKG_NAME, [
		'content_display_function' => 'protector_content_display',
		'content_preview_function' => 'protector_content_edit',
		'content_edit_function' => 'protector_content_edit',
		'content_store_function' => 'protector_content_store',
		'comment_store_function' => 'protector_comment_store',
		'content_expunge_function' => 'protector_content_expunge',
		'content_verify_access' => 'protector_content_verify_access',
		'content_list_sql_function' => 'protector_content_list',
		'content_load_sql_function' => 'protector_content_load',
		'content_edit_mini_tpl' => 'bitpackage:protector/choose_protection.tpl',
		'content_icon_tpl' => 'bitpackage:protector/protector_service_icon.tpl',
	] );
}

require_once PROTECTOR_PKG_CLASS_PATH .'LibertyProtector.php';
$gProtector = new LibertyProtector();
