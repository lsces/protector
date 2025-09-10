<?php
/**
 * protector package limits content based on user role
 *
 * @copyright (c) 2004-15 bitweaver.org
 * @package protector
 */

/**
 * required setup
 */
namespace Bitweaver\Liberty;

/**
 * Protector class to illustrate best practices when creating a new bitweaver package that
 * builds on core bitweaver functionality, such as the Liberty CMS engine
 *
 * @package protector
 */
class LibertyProtector extends LibertyBase {

	/**
    * During initialisation, be sure to call our base constructors
	**/
	function __construct( $pContentId=0 ) {
		$this->mContentId = $pContentId ;
		parent::__construct();
	}

    /**
    * Update the liberty_content_role_map table with corrected role_id(s).
    * 
    * In -1 for anonymouse is not stored, switching content to anonymouse will clear array
    *  
    * @param object $pParamHash
	*/
	function storeProtection( &$pParamHash ) {
		global $gBitSystem;
		if( \Bitweaver\BitBase::verifyId( $pParamHash['protector']['role_id'] ?? 0 ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", [ $pParamHash['content_id'] ] );
			if( $gBitSystem->isFeatureActive( 'protector_single_role' ) ) {
				if( $pParamHash['protector']['role_id'] != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_role_map` ( `role_id`, `content_id` ) VALUES ( ?, ? )", [ $pParamHash['protector']['role_id'], $pParamHash['content_id'] ] );
			} else {
				foreach( $pParamHash['protector']['role_id'] AS $roleId ) {
					if( $roleId != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_role_map` ( `role_id`, `content_id` ) VALUES ( ?, ? )", [ $roleId, $pParamHash['content_id'] ] );
				}
			}
		}
		return count( $this->mErrors ) == 0;
	}

    /**
    * Delete entry(ies) from liberty_content_role_map table with content_id.
    * 
    * @param object $pContent 
	*/
	public function expunge(): bool {
		if( \Bitweaver\BitBase::verifyId( $this->mContentId ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", [ $this->mContentId ] );
		}
		return true;
	}

    /**
    * @return array liberty_content_role_map for selected content_id
    * Ret -1 for anonymouse if alternatives are not stored
	**/
	public function getProtectionList( $ContentId=null ) {
		global $gBitSystem;
		$ret = [ '-1' <= $ContentId ];
		if( isset( $ContentId ) ) {
			$ret = $this->mDb->GetAssoc( "SELECT `role_id`, `content_id` FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", [ $ContentId ] );
		}
		return $ret;
	}
}

/**
* function to provide list of filtered content
**/
function protector_content_list() {
	global $gBitUser;
	$userId = $gBitUser->mUserId ?? 0;
	$roles = array_keys($gBitUser->getRoles( $userId ?? 0, true ));
	$ret = [
		'join_sql'  => " LEFT JOIN `" . BIT_DB_PREFIX . "liberty_content_role_map` lcrm ON ( lc.`content_id`=lcrm.`content_id` ) LEFT OUTER JOIN `" . BIT_DB_PREFIX . "users_roles_map` purm ON ( purm.`user_id` = " . $userId . " ) AND ( purm.`role_id`=lcrm.`role_id` ) ",
		'where_sql' => " AND (lcrm.`content_id` IS null OR lcrm.`role_id` IN(" . implode( ',', array_fill( 0, count( $roles ), '?' ) ) . " ) OR purm.`user_id` = ? ) ",
		'bind_vars' => array_merge( $roles, [ $userId ] ),
	];
	return $ret;
}

/**
 * function to load a filtered content element
 * 
 * @param object $pContent 
 */
function protector_content_load( $pContent = null ) {
	global $gBitUser;
	$userId = $gBitUser->mUserId ?? 0;
	$roles = array_keys($gBitUser->getRoles( $userId , true ));
	protector_content_verify_access( $pContent, $roles );
	$ret = [
		'join_sql'  => " LEFT JOIN `" . BIT_DB_PREFIX . "liberty_content_role_map` lcrm ON ( lc.`content_id`=lcrm.`content_id` ) LEFT OUTER JOIN `" . BIT_DB_PREFIX . "users_roles_map` purm ON ( purm.`role_id`=lcrm.`role_id` ) ",
		'where_sql' => " AND (lcrm.`content_id` IS null OR lcrm.`role_id` IN(" . implode( ',', array_fill( 0, count( $roles ), '?' ) ) . " ) OR purm.`user_id`=?) ",
		'bind_vars' => [ $userId ],
	];
	$ret['bind_vars'] = array_merge( $roles, $ret['bind_vars'] );
	return $ret;
}

/**
* function to store a filtered content element
* 
* @param object $pObject
* @param array $pParamHash 
**/
function protector_content_store( $pObject, $pParamHash ) {
	global $gBitSystem, $gProtector;
	$errors = null;
	// If a content access system is active, let's call it
	if( $gBitSystem->isPackageActive( 'protector' ) ) {
		if( !$gProtector->storeProtection( $pParamHash ) ) {
			$errors['protector'] = $gProtector->mErrors['security'];
		}
	}
	return $errors;
}

/**
* function to store a filtered comment element
* 
* @param object $pContent
* @param array $pParamHash 
**/
function protector_comment_store( $pContent, $pParamHash ) {
	global $gBitSystem, $gProtector;
	$errors = null;
	// If a content access system is active, let's call it
	if( $gBitSystem->isPackageActive( 'protector' ) ) {
		if( isset( $pParamHash['comments_parent_id'] ) ) {
			$pParamHash['protector']['role_id'] = $pContent->mDb->GetOne( "SELECT `role_id` FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", [ $pParamHash['comments_parent_id'] ] );
		}
		if( !$gProtector->storeProtection( $pParamHash ) ) {
			$errors['protector'] = $gProtector->mErrors['security'];
		}
	}
	return $errors;
}

/**
* function to delete a filtered content element
* 
* @param object $pContent
* @param array $pParamHash 
**/
function protector_content_expunge( $pContent = null ) {
		if( \Bitweaver\BitBase::verifyId( $pContent->mContentId ) ) {
			$pContent->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", [ $pContent->mContentId ] );
		}
}

/**
* function to display a filtered content element
* 
* @param object $pContent
* @param array $pParamHash 
**/
function protector_content_display( &$pContent, &$pParamHash ) {
	global $gBitSystem, $gBitSmarty;
	$pContent->hasUserPermission( $pParamHash['perm_name'] ?? '' );
}

/**
* function to verify access to a filtered content element
* 
* @param object $pContent
* @param array $pHash 
**/
function protector_content_verify_access( $pContent, $pHash ) {
	global $gBitUser, $gBitSystem;

	$error = null;
	if ( $pContent && $pContent->isValid() ) {
		if( !$pContent->verifyId( $pContent->mContentId ) ) {
		}
		if( $pContent->verifyId( $pContent->mContentId ) ) {
			$query = "SELECT lc.`content_id`, lcrm.`role_id` as `is_protected`
				FROM `".BIT_DB_PREFIX."liberty_content` lc 
				LEFT JOIN `".BIT_DB_PREFIX."liberty_content_role_map` lcrm ON ( lc.`content_id`=lcrm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_roles_map` urm ON ( urm.`user_id`=". ( $gBitUser->mUserId ?? 0 ) ." ) AND ( urm.`role_id`=lcrm.`role_id` ) 
				WHERE lc.`content_id` = ?";
			$ret = $pContent->mDb->getRow( $query, [ $pContent->mContentId ] );
			if( $ret and is_numeric($ret['is_protected']) and !in_array( $ret['is_protected'], $pHash ) ) {
				$gBitSystem->fatalPermission( 'protector permission fail' );
			} else {
				if ( $ret and is_numeric($ret['is_protected']) and $ret['is_protected'] == -1 )
					$pContent->mViewPublic = 'public';
			}
		}
	}
	return $error;
}

/**
* function to edit a filtered content element
* 
* @param object $pContent
**/
function protector_content_edit( $pContent, $pFunctionParam ) {
	global $gProtector, $gBitUser, $gBitSmarty;
	$roles = $gBitUser->getRoles();
	$roles[-1]['role_name'] = "~~ System Default ~~";
	ksort( $roles );
	foreach( array_keys( $roles ) as $roleId ) {
		$protectorRolesId[$roleId] = $roleId != -1 ? $roles[$roleId]['role_name'] : "~~ System Default ~~";
	}
	if ( $pContent->mContentId ) {
		$serviceHash['protector']['role'] = $gProtector->getProtectionList( $pContent->mContentId );
	} else {
		if ( isset( $pContent->mInfo['parent_id'] ) ) {
			$serviceHash['protector']['role'] = $gProtector->getProtectionList( $pContent->mInfo['parent_id'] );
		}
	}	
	if ( isset( $serviceHash['protector']['role'] ) ) { $prot = array_keys( $serviceHash['protector']['role'] ); }
	$serviceHash['protector']['role_id'] = empty( $prot[0] ) ? -1 : $prot[0];
	$gBitSmarty->assign( 'serviceHash', $serviceHash );
	$gBitSmarty->assign( 'protectorRolesId', $protectorRolesId );
	$gBitSmarty->assign( 'protectorRoles', $roles );
}
