<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate 2-9-2010 14:43
 */

if( ! defined( 'NV_IS_FILE_ADMIN' ) ) die( 'Stop!!!' );

if( defined( 'NV_EDITOR' ) )
{
	require_once NV_ROOTDIR . '/' . NV_EDITORSDIR . '/' . NV_EDITOR . '/nv.php';
}

if( empty( $global_array_cat ) )
{
	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=cat' );
	die();
}

$table_name = TABLE_SHOPS_NAME . '_rows';
$month_dir_module = nv_mkdir( NV_UPLOADS_REAL_DIR . '/' . $module_name, date( 'Y_m' ), true );
$array_block_cat_module = array();
$id_block_content = array();
$array_custom = array();
$array_custom_lang = array();

$sql = 'SELECT bid, adddefault, ' . NV_LANG_DATA . '_title FROM ' . TABLE_SHOPS_NAME . '_block_cat ORDER BY weight ASC';
$result = $db->query( $sql );
while( list( $bid_i, $adddefault_i, $title_i ) = $result->fetch( 3 ) )
{
	$array_block_cat_module[$bid_i] = $title_i;
	if( $adddefault_i )
	{
		$id_block_content[] = $bid_i;
	}
}

$catid = $nv_Request->get_int( 'catid', 'get', 0 );
$parentid = $nv_Request->get_int( 'parentid', 'get', 0 );

$stmt = $db->prepare( 'SELECT numsubcat FROM ' . TABLE_SHOPS_NAME . '_catalogs WHERE catid= :parentid' );
$stmt->bindParam( ':parentid', $parentid, PDO::PARAM_STR );
$stmt->execute();
$subcatid = $stmt->fetchColumn();
if( $subcatid > 0 )
{
	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name );
	die();
}

$data = array(
	'id' => 0,
	'listcatid' => $catid,
	'user_id' => $admin_info['admin_id'],
	'addtime' => NV_CURRENTTIME,
	'edittime' => NV_CURRENTTIME,
	'status' => 0,
	'publtime' => NV_CURRENTTIME,
	'exptime' => 0,
	'archive' => 1,
	'product_code' => '',
	'product_number' => 1,
	'product_price' => 1,
	'discount_id' => 0,
	'money_unit' => $pro_config['money_unit'],
	'product_unit' => '',
	'homeimgfile' => '',
	'homeimgthumb' => '',
	'homeimgalt' => '',
	'imgposition' => 0,
	'copyright' => 0,
	'inhome' => 1,
	'allowed_comm' => $module_config[$module_name]['setcomm'],
	'allowed_rating' => 1,
	'ratingdetail' => '0',
	'allowed_send' => 1,
	'allowed_print' => 1,
	'allowed_save' => 1,
	'hitstotal' => 0,
	'hitscm' => 0,
	'hitslm' => 0,
	'showprice' => 1,
	'com_id' => 0,
	'title' => '',
	'alias' => '',
	'hometext' => '',
	'bodytext' => '',
	'note' => '',
	'keywords' => '',
	'keywords_old' => '',
	'warranty' => '',
	'promotional' => '',
	'vat' => 0,
	'typeproduct' => 0,
	'new_old' => 0,
	'adddefaul' => 1,
	'percentnew' => 90 );

$custom = array();

$page_title = $lang_module['content_add'];
$error = '';
$groups_list = nv_groups_list();

$data['id'] = $nv_Request->get_int( 'id', 'get,post', 0 );

$group_id_old = array();
if( $data['id'] > 0 )
{
	$group_id_old = getGroupID( $data['id'] );

	$array_keywords_old = array();
	$_query = $db->query( 'SELECT tid, ' . NV_LANG_DATA . '_keyword FROM ' . TABLE_SHOPS_NAME . '_tags_id WHERE id=' . $data['id'] . ' ORDER BY ' . NV_LANG_DATA . '_keyword ASC' );
	while( $row = $_query->fetch() )
	{
		$array_keywords_old[$row['tid']] = $row[NV_LANG_DATA . '_keyword'];
	}
	$data['keywords'] = implode( ', ', $array_keywords_old );
	$data['keywords_old'] = $data['keywords'];

}

if( $nv_Request->get_int( 'save', 'post' ) == 1 )
{
	$field_lang = nv_file_table( $table_name );
	$id_block_content = array_unique( $nv_Request->get_typed_array( 'bids', 'post', 'int', array() ) );
	$data['listcatid'] = $nv_Request->get_int( 'catid', 'post', 0 );
	$data['group_id'] = array_unique( $nv_Request->get_typed_array( 'groupids', 'post', 'int', array() ) );
	$data['showprice'] = $nv_Request->get_int( 'showprice', 'post', 0 );
	$data['showorder'] = $nv_Request->get_int( 'showorder', 'post', 0 );
	$publ_date = $nv_Request->get_title( 'publ_date', 'post', '' );
	$exp_date = $nv_Request->get_title( 'exp_date', 'post', '' );

	if( ! empty( $publ_date ) and ! preg_match( '/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $publ_date ) ) $publ_date = '';
	if( ! empty( $exp_date ) and ! preg_match( '/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $exp_date ) ) $exp_date = '';

	if( empty( $publ_date ) )
	{
		$data['publtime'] = NV_CURRENTTIME;
	}
	else
	{
		$phour = $nv_Request->get_int( 'phour', 'post', 0 );
		$pmin = $nv_Request->get_int( 'pmin', 'post', 0 );
		unset( $m );
		preg_match( '/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $publ_date, $m );
		$data['publtime'] = mktime( $phour, $pmin, 0, $m[2], $m[1], $m[3] );
	}

	if( empty( $exp_date ) )
	{
		$data['exptime'] = 0;
	}
	else
	{
		$ehour = $nv_Request->get_int( 'ehour', 'post', 0 );
		$emin = $nv_Request->get_int( 'emin', 'post', 0 );
		unset( $m );
		preg_match( '/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $exp_date, $m );
		$data['exptime'] = mktime( $ehour, $emin, 0, $m[2], $m[1], $m[3] );
	}

	$data['archive'] = $nv_Request->get_int( 'archive', 'post', 0 );

	if( $data['archive'] > 0 )
	{
		$data['archive'] = ( $data['exptime'] > NV_CURRENTTIME ) ? 1 : 2;
	}

	$data['title'] = nv_substr( $nv_Request->get_title( 'title', 'post', '', 1 ), 0, 255 );
	$data['note'] = $nv_Request->get_title( 'note', 'post', '', 1 );
	$data['address'] = $nv_Request->get_title( 'address', 'post', '', 1 );
	$data['warranty'] = $nv_Request->get_title( 'warranty', 'post', '', 1 );
	$data['promotional'] = $nv_Request->get_title( 'promotional', 'post', '', 1 );

	$alias = nv_substr( $nv_Request->get_title( 'alias', 'post', '', 1 ), 0, 255 );
	$data['alias'] = ( $alias == '' ) ? change_alias( $data['title'] ) : change_alias( $alias );
	$data['alias'] = strtolower( $data['alias'] );

	$data['hometext'] = $nv_Request->get_textarea( 'hometext', 'post', '', 'br', 1 );
	$data['product_code'] = nv_substr( $nv_Request->get_title( 'product_code', 'post', '', 1 ), 0, 255 );
	$data['product_number'] = $nv_Request->get_int( 'product_number', 'post', 0 );
	$data['product_price'] = $nv_Request->get_string( 'product_price', 'post', '' );
	$data['product_price'] = floatval( preg_replace( '/[^0-9\.]/', '', $data['product_price'] ) );

	$data['discount_id'] = $nv_Request->get_int( 'discount_id', 'post', 0 );
	$data['money_unit'] = $nv_Request->get_string( 'money_unit', 'post', '' );
	$data['product_unit'] = $nv_Request->get_int( 'product_unit', 'post', 0 );
	$data['homeimgfile'] = $nv_Request->get_title( 'homeimg', 'post', '' );
	$data['homeimgalt'] = $nv_Request->get_title( 'homeimgalt', 'post', '', 1 );

	$bodytext = $nv_Request->get_string( 'bodytext', 'post', '' );
	$data['bodytext'] = defined( 'NV_EDITOR' ) ? nv_nl2br( $bodytext, '' ) : nv_nl2br( nv_htmlspecialchars( strip_tags( $bodytext ) ), '<br />' );

	$data['copyright'] = ( int )$nv_Request->get_bool( 'copyright', 'post' );
	$data['inhome'] = ( int )$nv_Request->get_bool( 'inhome', 'post' );

	$_groups_post = $nv_Request->get_array( 'allowed_comm', 'post', array() );
	$data['allowed_comm'] = ! empty( $_groups_post ) ? implode( ',', nv_groups_post( array_intersect( $_groups_post, array_keys( $groups_list ) ) ) ) : '';

	$data['allowed_rating'] = ( int )$nv_Request->get_bool( 'allowed_rating', 'post' );
	$data['allowed_send'] = ( int )$nv_Request->get_bool( 'allowed_send', 'post' );
	$data['allowed_print'] = ( int )$nv_Request->get_bool( 'allowed_print', 'post' );
	$data['allowed_save'] = ( int )$nv_Request->get_bool( 'allowed_save', 'post' );

	$data['keywords'] = $nv_Request->get_array( 'keywords', 'post', '' );
	$data['keywords'] = implode( ', ', $data['keywords'] );
	$data['new_old'] = $nv_Request->get_int( 'new_old', 'post', 0 );
	$data['typeproduct'] = $nv_Request->get_int( 'typeproduct', 'post', 0 );
	$data['vat'] = $nv_Request->get_int( 'vat', 'post', 0 );
	$data['percentnew'] = $nv_Request->get_int( 'percentnew', 'post', 0 );
	$data['adddefaul'] = $nv_Request->get_int( 'adddefaul', 'post', 0 );

	$array_custom = $nv_Request->get_array( 'custom', 'post' );
	$array_custom_lang = $nv_Request->get_array( 'custom_lang', 'post' );

	$custom = $array_custom;
	$data['custom_site'] = ( ! empty( $array_custom ) ) ? serialize( $array_custom ) : '';
	$data['custom'] = ( ! empty( $array_custom_lang ) ) ? serialize( $array_custom_lang ) : '';

	// Xu ly anh minh hoa khac
	$otherimage = $nv_Request->get_typed_array( 'otherimage', 'post', 'string' );
	$array_otherimage = array();
	foreach( $otherimage as $otherimage_i )
	{
		if( ! nv_is_url( $otherimage_i ) and file_exists( NV_DOCUMENT_ROOT . $otherimage_i ) )
		{
			$lu = strlen( NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_name . '/' );
			$otherimage_i = substr( $otherimage_i, $lu );
		}
		elseif( ! nv_is_url( $otherimage_i ) )
		{
			$otherimage_i = '';
		}
		if( ! empty( $otherimage_i ) )
		{
			$array_otherimage[] = $otherimage_i;
		}
	}
	$data['otherimage'] = implode( '|', $array_otherimage );

	// Kiem tra ma san pham trung
	$error_product_code = false;
	if( ! empty( $data['product_code'] ) )
	{
		$stmt = $db->prepare( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_rows WHERE product_code= :product_code AND id!=' . $data['id'] );
		$stmt->bindParam( ':product_code', $data['product_code'], PDO::PARAM_STR );
		$stmt->execute();
		$id_err = $stmt->rowCount();

		$stmt = $db->prepare( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_rows WHERE product_code= :product_code' );
		$stmt->bindParam( ':product_code', $data['product_code'], PDO::PARAM_STR );
		$stmt->execute();
		if( $data['id'] == 0 and $stmt->rowCount() )
		{
			$error_product_code = true;
		}
		elseif( $id_err )
		{
			$error_product_code = true;
		}
	}

	if( empty( $data['title'] ) )
	{
		$error = $lang_module['error_title'];
	}
	elseif( $error_product_code )
	{
		$error = $lang_module['error_product_code'];
	}
	elseif( empty( $data['listcatid'] ) )
	{
		$error = $lang_module['error_cat'];
	}
	elseif( trim( strip_tags( $data['hometext'] ) ) == '' )
	{
		$error = $lang_module['error_hometext'];
	}
	elseif( trim( strip_tags( $data['bodytext'] ) ) == '' )
	{
		$error = $lang_module['error_bodytext'];
	}
	elseif( $data['product_price'] <= 0 and $data['showprice'] )
	{
		$error = $lang_module['error_product_price'];
	}
	else
	{
		// Xu ly tu khoa
		if( $data['keywords'] == '' )
		{
			$keywords = ( $data['hometext'] != '' ) ? $data['hometext'] : $data['bodyhtml'];
			$keywords = nv_get_keywords( $keywords, 100 );
			$keywords = explode( ',', $keywords );

			// Ưu tiên lọc từ khóa theo các từ khóa đã có trong tags thay vì đọc từ từ điển
			$keywords_return = array();
			$sth = $db->prepare( 'SELECT COUNT(*) FROM ' . $db_config['prefix'] . "_" . $module_data . '_tags_id where ' . NV_LANG_DATA . '_keyword = :keyword' );
			foreach( $keywords as $keyword_i )
			{
				$sth->bindParam( ':keyword', $keyword_i, PDO::PARAM_STR );
				$sth->execute();
				if( $sth->fetchColumn() )
				{
					$keywords_return[] = $keyword_i;
					if( sizeof( $keywords_return ) > 20 )
					{
						break;
					}
				}
			}

			if( sizeof( $keywords_return ) < 20 )
			{
				foreach( $keywords as $keyword_i )
				{
					if( ! in_array( $keyword_i, $keywords_return ) )
					{
						$keywords_return[] = $keyword_i;
						if( sizeof( $keywords_return ) > 20 )
						{
							break;
						}
					}
				}
			}
			$data['keywords'] = implode( ',', $keywords );
		}
		$data['status'] = ( $nv_Request->isset_request( 'status1', 'post' ) ) ? 1 : 0;

		// Xu ly anh minh hoa
		$data['homeimgthumb'] = 0;
		if( ! nv_is_url( $data['homeimgfile'] ) and is_file( NV_DOCUMENT_ROOT . $data['homeimgfile'] ) )
		{
			$lu = strlen( NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_name . '/' );
			$data['homeimgfile'] = substr( $data['homeimgfile'], $lu );
			if( file_exists( NV_ROOTDIR . '/' . NV_FILES_DIR . '/' . $module_name . '/' . $data['homeimgfile'] ) )
			{
				$data['homeimgthumb'] = 1;
			}
			else
			{
				$data['homeimgthumb'] = 2;
			}
		}
		elseif( nv_is_url( $data['homeimgfile'] ) )
		{
			$data['homeimgthumb'] = 3;
		}
		else
		{
			$data['homeimgfile'] = '';
		}

		$listfield = '';
		$listvalue = '';
		foreach( $field_lang as $field_lang_i )
		{
			list( $flang, $fname ) = $field_lang_i;
			$listfield .= ', ' . $flang . '_' . $fname;
			$listvalue .= ', :' . $flang . '_' . $fname;
		}

		if( $data['id'] == 0 )
		{

			$data['publtime'] = ( $data['publtime'] > NV_CURRENTTIME ) ? $data['publtime'] : NV_CURRENTTIME;
			if( $data['status'] == 1 and $data['publtime'] > NV_CURRENTTIME )
			{
				$data['status'] = 2;
			}

			$sql = "INSERT INTO " . $db_config['prefix'] . "_" . $module_data . "_rows (id, listcatid, user_id, addtime, edittime, status, publtime, exptime, archive, product_code, product_number, product_price, money_unit, product_unit, discount_id, homeimgfile, homeimgthumb, homeimgalt,otherimage,imgposition, copyright, inhome, allowed_comm, allowed_rating, ratingdetail, allowed_send, allowed_print, allowed_save, hitstotal, hitscm, hitslm, showprice, custom,vat,typeproduct,new_old,percentnew,adddefaul " . $listfield . ")
				 VALUES ( NULL ,
				 :listcatid,
				 " . intval( $data['user_id'] ) . ",
				 " . intval( $data['addtime'] ) . ",
				 " . intval( $data['edittime'] ) . ",
				 " . intval( $data['status'] ) . ",
				 " . intval( $data['publtime'] ) . ",
				 " . intval( $data['exptime'] ) . ",
				 " . intval( $data['archive'] ) . ",
				 :product_code,
				 " . intval( $data['product_number'] ) . ",
				 :product_price,
				 :money_unit,
				 " . intval( $data['product_unit'] ) . ",
				 " . intval( $data['discount_id'] ) . ",
				 :homeimgfile,
				 :homeimgthumb,
				 :homeimgalt,
				 :otherimage,
				 " . intval( $data['imgposition'] ) . ",
				 " . intval( $data['copyright'] ) . ",
				 " . intval( $data['inhome'] ) . ",
				 :allowed_comm,
				 " . intval( $data['allowed_rating'] ) . ",
				 :ratingdetail,
				 " . intval( $data['allowed_send'] ) . ",
				 " . intval( $data['allowed_print'] ) . ",
				 " . intval( $data['allowed_save'] ) . ",
				 " . intval( $data['hitstotal'] ) . ",
				 " . intval( $data['hitscm'] ) . ",
				 " . intval( $data['hitslm'] ) . ",
				 " . intval( $data['showprice'] ) . ",
				 :custom,
				 " . intval( $data['vat'] ) . ",
				 " . intval( $data['typeproduct'] ) . ",
				 " . intval( $data['new_old'] ) . ",
				 " . intval( $data['percentnew'] ) . ",
				 " . intval( $data['adddefaul'] ) . "
				" . $listvalue . "
			)";

			$data_insert = array();
			$data_insert['listcatid'] = $data['listcatid'];
			$data_insert['product_code'] = $data['product_code'];
			$data_insert['product_price'] = $data['product_price'];
			$data_insert['money_unit'] = $data['money_unit'];
			$data_insert['homeimgfile'] = $data['homeimgfile'];
			$data_insert['homeimgthumb'] = $data['homeimgthumb'];
			$data_insert['homeimgalt'] = $data['homeimgalt'];
			$data_insert['otherimage'] = $data['otherimage'];
			$data_insert['ratingdetail'] = $data['ratingdetail'];
			$data_insert['allowed_comm'] = $data['allowed_comm'];
			$data_insert['custom'] = $data['custom_site'];

			foreach( $field_lang as $field_lang_i )
			{
				list( $flang, $fname ) = $field_lang_i;
				$data_insert[$flang . '_' . $fname] = $data[$fname];
			}

			$data['id'] = $db->insert_id( $sql, 'catid', $data_insert );

			if( $data['id'] > 0 )
			{
				//denday
				if( $global_array_cat[$data['listcatid']]['form'] != '' )
				{
					// $form = $db->query( 'SELECT form FROM '.TABLE_SHOPS_NAME . '_catalogs where catid=' . $data['listcatid'] )->fetchColumn();

					$idtemplate = $db->query( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_template where title= "cat_form_' . $global_array_cat[$data['listcatid']]['form'] . '"' )->fetchColumn();

					$table_insert = $db_config['prefix'] . "_" . $module_data . "_info_" . $idtemplate;

					Insertabl_catfields( $table_insert, $array_custom, $data['id'] );
				}
				if( ! empty( $data['group_id'] ) )
				{
					$stmt = $db->prepare( 'INSERT INTO ' . TABLE_SHOPS_NAME . '_items_group VALUES(' . $data['id'] . ', :group_id)' );

					foreach( $data['group_id'] as $group_id_i )
					{
						$stmt->bindParam( ':group_id', $group_id_i, PDO::PARAM_STR );
						$stmt->execute();
					}
				}

				$auto_product_code = '';
				if( ! empty( $pro_config['format_code_id'] ) and empty( $data['product_code'] ) )
				{
					$i = 1;
					$auto_product_code = vsprintf( $pro_config['format_code_id'], $data['id'] );

					$stmt = $db->prepare( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_rows WHERE product_code= :product_code' );
					$stmt->bindParam( ':product_code', $auto_product_code, PDO::PARAM_STR );
					$stmt->execute();
					while( $stmt->rowCount() )
					{
						$auto_product_code = vsprintf( $pro_config['format_code_id'], ( $data['id'] + $i++ ) );
					}

					$stmt = $db->prepare( 'UPDATE ' . TABLE_SHOPS_NAME . '_rows SET product_code= :product_code WHERE id=' . $data['id'] );
					$stmt->bindParam( ':product_code', $auto_product_code, PDO::PARAM_STR );
					$stmt->execute();
				}

				nv_fix_group_count( $data['group_id'] );
				nv_insert_logs( NV_LANG_DATA, $module_name, 'Add A Product', 'ID: ' . $data['id'], $admin_info['userid'] );
			}
			else
			{
				$error = $lang_module['errorsave'];
			}
		}
		else
		{
			$data_old = $db->query( 'SELECT * FROM ' . TABLE_SHOPS_NAME . '_rows where id=' . $data['id'] )->fetch();

			$data['user_id'] = $data_old['user_id'];

			if( $data_old['status'] == 1 )
			{
				$data['status'] = 1;
			}
			if( intval( $data['publtime'] ) < intval( $data_old['addtime'] ) )
			{
				$data['publtime'] = $data_old['addtime'];
			}

			if( $data['status'] == 1 and $data['publtime'] > NV_CURRENTTIME )
			{
				$data['status'] = 2;
			}

			$stmt = $db->prepare( "UPDATE " . $db_config['prefix'] . "_" . $module_data . "_rows SET
			 listcatid= :listcatid,
			 user_id=" . intval( $data['user_id'] ) . ",
			 status=" . intval( $data['status'] ) . ",
			 publtime=" . intval( $data['publtime'] ) . ",
			 exptime=" . intval( $data['exptime'] ) . ",
			 edittime= " . NV_CURRENTTIME . " ,
			 archive=" . intval( $data['archive'] ) . ",
			 product_code = :product_code,
			 product_number = product_number + " . intval( $data['product_number'] ) . ",
			 product_price = :product_price,
			 money_unit = :money_unit,
			 product_unit = " . intval( $data['product_unit'] ) . ",
			 discount_id = " . intval( $data['discount_id'] ) . ",
			 homeimgfile= :homeimgfile,
			 homeimgalt= :homeimgalt,
			 otherimage= :otherimage,
			 homeimgthumb= :homeimgthumb,
			 imgposition=" . intval( $data['imgposition'] ) . ",
			 copyright=" . intval( $data['copyright'] ) . ",
			 inhome=" . intval( $data['inhome'] ) . ",
			 allowed_comm= :allowed_comm,
			 allowed_rating=" . intval( $data['allowed_rating'] ) . ",
			 allowed_send=" . intval( $data['allowed_send'] ) . ",
			 allowed_print=" . intval( $data['allowed_print'] ) . ",
			 allowed_save=" . intval( $data['allowed_save'] ) . ",
			 showprice = " . intval( $data['showprice'] ) . ",
			 
			 vat = " . intval( $data['vat'] ) . ",
			 typeproduct = " . intval( $data['typeproduct'] ) . ",
			 new_old = " . intval( $data['new_old'] ) . ",
			 percentnew = " . intval( $data['percentnew'] ) . ",
			 adddefaul = " . intval( $data['adddefaul'] ) . ",
			 
			 custom= :custom,
			 " . NV_LANG_DATA . "_title= :title,
			  " . NV_LANG_DATA . "_address= :address,
			 " . NV_LANG_DATA . "_alias= :alias,
			 " . NV_LANG_DATA . "_hometext= :hometext,
			 " . NV_LANG_DATA . "_bodytext= :bodytext,
			 " . NV_LANG_DATA . "_warranty= :warranty,
			 " . NV_LANG_DATA . "_promotional= :promotional,
			 " . NV_LANG_DATA . "_custom= :custom_lang
			 WHERE id =" . $data['id'] );

			$stmt->bindParam( ':listcatid', $data['listcatid'], PDO::PARAM_STR );
			$stmt->bindParam( ':product_code', $data['product_code'], PDO::PARAM_STR );
			$stmt->bindParam( ':money_unit', $data['money_unit'], PDO::PARAM_STR );
			$stmt->bindParam( ':product_price', $data['product_price'], PDO::PARAM_STR );
			$stmt->bindParam( ':homeimgfile', $data['homeimgfile'], PDO::PARAM_STR );
			$stmt->bindParam( ':homeimgalt', $data['homeimgalt'], PDO::PARAM_STR );
			$stmt->bindParam( ':otherimage', $data['otherimage'], PDO::PARAM_STR );
			$stmt->bindParam( ':homeimgthumb', $data['homeimgthumb'], PDO::PARAM_STR );
			$stmt->bindParam( ':title', $data['title'], PDO::PARAM_STR );
			$stmt->bindParam( ':address', $data['address'], PDO::PARAM_STR );
			$stmt->bindParam( ':alias', $data['alias'], PDO::PARAM_STR );
			$stmt->bindParam( ':hometext', $data['hometext'], PDO::PARAM_STR, strlen( $data['hometext'] ) );
			$stmt->bindParam( ':bodytext', $data['bodytext'], PDO::PARAM_STR, strlen( $data['bodytext'] ) );
			$stmt->bindParam( ':promotional', $data['promotional'], PDO::PARAM_STR );
			$stmt->bindParam( ':warranty', $data['warranty'], PDO::PARAM_STR );
			$stmt->bindParam( ':allowed_comm', $data['allowed_comm'], PDO::PARAM_STR );
			$stmt->bindParam( ':custom', $data['custom_site'], PDO::PARAM_STR, strlen( $data['custom_site'] ) );
			$stmt->bindParam( ':custom_lang', $data['custom'], PDO::PARAM_STR, strlen( $data['custom'] ) );

			if( $stmt->execute() )
			{
				if( $group_id_old != $data['group_id'] )
				{
					$sql = 'DELETE FROM ' . TABLE_SHOPS_NAME . '_items_group WHERE pro_id = ' . $data['id'];
					$db->query( $sql );

					if( ! empty( $data['group_id'] ) )
					{
						$stmt = $db->prepare( 'INSERT INTO ' . TABLE_SHOPS_NAME . '_items_group VALUES(' . $data['id'] . ', :group_id)' );
						foreach( $data['group_id'] as $group_id_i )
						{
							$stmt->bindParam( ':group_id', $group_id_i, PDO::PARAM_STR );
							$stmt->execute();
						}
					}

					nv_fix_group_count( $data['group_id'] );
					nv_fix_group_count( $group_id_old );
				}
				nv_insert_logs( NV_LANG_DATA, $module_name, 'Edit A Product', 'ID: ' . $data['id'], $admin_info['userid'] );
				//tim va xoa du lieu tuy bien
				if( $global_array_cat[$data_old['listcatid']]['form'] != '' )
				{
					$idtemplate = $db->query( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_template where title= "cat_form_' . $global_array_cat[$data_old['listcatid']]['form'] . '"' )->fetchColumn();

					$table_insert = $db_config['prefix'] . "_" . $module_data . "_info_" . $idtemplate;

					$db->query( "DELETE FROM " . $table_insert . " WHERE shopid =" . $data['id'] );
				}
				if( $global_array_cat[$data['listcatid']]['form'] != '' )
				{
					//insert lai du lieu tuy bien
					$idtemplate_new = $db->query( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_template where title= "cat_form_' . $global_array_cat[$data['listcatid']]['form'] . '"' )->fetchColumn();
					$table_insert_new = $db_config['prefix'] . "_" . $module_data . "_info_" . $idtemplate_new;
					//print_r($data['group_id']);die;
					/*
					foreach ($data['group_id'] as $key => $value) {
					if ($value <= 13 && $value >= 2) {
					$array_custom['chieudaimattien'] = $value;
					}
					
					}*/

					$array_custom['link'] = $nv_Request->get_string( 'bodytext1', 'get,post', 0 );
					//$array_custom['link']='jshjsd';

					//print_r($array_custom);die(''.$data['id']);
					Insertabl_catfields( $table_insert_new, $array_custom, $data['id'] );
				}

			}
			else
			{
				$error = $lang_module['errorsave'];
			}
		}

		nv_set_status_module();

		if( $error == '' )
		{
			$db->query( 'DELETE FROM ' . TABLE_SHOPS_NAME . '_block WHERE id = ' . $data['id'] );

			foreach( $id_block_content as $bid_i )
			{
				$db->query( "INSERT INTO " . $db_config['prefix'] . "_" . $module_data . "_block (bid, id, weight) VALUES ('" . $bid_i . "', '" . $data['id'] . "', '0')" );
			}

			foreach( $array_block_cat_module as $bid_i )
			{
				nv_news_fix_block( $bid_i );
			}

			// Update tags list
			if( $data['keywords'] != $data['keywords_old'] )
			{
				$keywords = explode( ',', $data['keywords'] );
				$keywords = array_map( 'strip_punctuation', $keywords );
				$keywords = array_map( 'trim', $keywords );
				$keywords = array_diff( $keywords, array( '' ) );
				$keywords = array_unique( $keywords );

				foreach( $keywords as $keyword )
				{
					if( ! in_array( $keyword, $array_keywords_old ) )
					{
						$alias_i = ( $module_config[$module_name]['tags_alias'] ) ? change_alias( $keyword ) : str_replace( ' ', '-', $keyword );
						$alias_i = nv_strtolower( $alias_i );
						$sth = $db->prepare( 'SELECT tid, ' . NV_LANG_DATA . '_alias, ' . NV_LANG_DATA . '_description, ' . NV_LANG_DATA . '_keywords FROM ' . TABLE_SHOPS_NAME . '_tags where ' . NV_LANG_DATA . '_alias= :alias OR FIND_IN_SET(:keyword, ' . NV_LANG_DATA . '_keywords)>0' );
						$sth->bindParam( ':alias', $alias_i, PDO::PARAM_STR );
						$sth->bindParam( ':keyword', $keyword, PDO::PARAM_STR );
						$sth->execute();

						list( $tid, $alias, $keywords_i ) = $sth->fetch( 3 );
						if( empty( $tid ) )
						{
							$array_insert = array();
							$array_insert['alias'] = $alias_i;
							$array_insert['keyword'] = $keyword;

							$tid = $db->insert_id( "INSERT INTO " . $db_config['prefix'] . "_" . $module_data . "_tags (" . NV_LANG_DATA . "_numpro, " . NV_LANG_DATA . "_alias, " . NV_LANG_DATA . "_description, " . NV_LANG_DATA . "_image, " . NV_LANG_DATA . "_keywords) VALUES (1, :alias, '', '', :keyword)", "tid", $array_insert );
						}
						else
						{
							if( $alias != $alias_i )
							{
								if( ! empty( $keywords_i ) )
								{
									$keyword_arr = explode( ',', $keywords_i );
									$keyword_arr[] = $keyword;
									$keywords_i2 = implode( ',', array_unique( $keyword_arr ) );
								}
								else
								{
									$keywords_i2 = $keyword;
								}
								if( $keywords_i != $keywords_i2 )
								{
									$sth = $db->prepare( 'UPDATE ' . TABLE_SHOPS_NAME . '_tags SET ' . NV_LANG_DATA . '_keywords= :keywords WHERE tid =' . $tid );
									$sth->bindParam( ':keywords', $keywords_i2, PDO::PARAM_STR );
									$sth->execute();
								}
							}
							$db->query( 'UPDATE ' . TABLE_SHOPS_NAME . '_tags SET ' . NV_LANG_DATA . '_numpro = ' . NV_LANG_DATA . '_numpro+1 WHERE tid = ' . $tid );
						}

						// insert keyword for table _tags_id
						try
						{
							$sth = $db->prepare( 'INSERT INTO ' . TABLE_SHOPS_NAME . '_tags_id (id, tid, ' . NV_LANG_DATA . '_keyword) VALUES (' . $data['id'] . ', ' . intval( $tid ) . ', :keyword)' );
							$sth->bindParam( ':keyword', $keyword, PDO::PARAM_STR );
							$sth->execute();
						}
						catch ( PDOException $e )
						{
							$sth = $db->prepare( 'UPDATE ' . TABLE_SHOPS_NAME . '_tags_id SET ' . NV_LANG_DATA . '_keyword = :keyword WHERE id = ' . $data['id'] . ' AND tid=' . intval( $tid ) );
							$sth->bindParam( ':keyword', $keyword, PDO::PARAM_STR );
							$sth->execute();
						}
						unset( $array_keywords_old[$tid] );
					}
				}

				foreach( $array_keywords_old as $tid => $keyword )
				{
					if( ! in_array( $keyword, $keywords ) )
					{
						$db->query( 'UPDATE ' . TABLE_SHOPS_NAME . '_tags SET ' . NV_LANG_DATA . '_numpro = ' . NV_LANG_DATA . '_numpro-1 WHERE tid = ' . $tid );
						$db->query( 'DELETE FROM ' . TABLE_SHOPS_NAME . '_tags_id WHERE id = ' . $data['id'] . ' AND tid=' . $tid );
					}
				}
			}

			nv_del_moduleCache( $module_name );
			Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=items' );
			die();
		}

		nv_del_moduleCache( $module_name );
	}
}
elseif( $data['id'] > 0 )
{
	$keyword = $data['keywords'];
	$data = $db->query( "SELECT * FROM " . $db_config['prefix'] . "_" . $module_data . "_rows where id=" . $data['id'] )->fetch();
	$data['title'] = $data[NV_LANG_DATA . '_title'];
	$data['alias'] = $data[NV_LANG_DATA . '_alias'];
	$data['hometext'] = $data[NV_LANG_DATA . '_hometext'];
	$data['bodytext'] = $data[NV_LANG_DATA . '_bodytext'];
	$data['promotional'] = $data[NV_LANG_DATA . '_promotional'];
	$data['warranty'] = $data[NV_LANG_DATA . '_warranty'];
	$data['address'] = $data[NV_LANG_DATA . '_address'];
	$data['group_id'] = $group_id_old;
	$data['keywords'] = $keyword;

	if( ! empty( $data['custom'] ) )
	{
		$array_custom = unserialize( $data['custom'] );
	}
	if( ! empty( $data[NV_LANG_DATA . '_custom'] ) )
	{
		$array_custom_lang = unserialize( $data[NV_LANG_DATA . '_custom'] );
	}

	$page_title = $lang_module['content_edit'];

	//tuybien

	$idtemplate = $db->query( 'SELECT id FROM ' . TABLE_SHOPS_NAME . '_template where title= "cat_form_' . $global_array_cat[$data['listcatid']]['form'] . '"' )->fetchColumn();

	if( $idtemplate )
	{
		$table_insert = $db_config['prefix'] . "_" . $module_data . "_info_" . $idtemplate;

		$custom = $db->query( "SELECT * FROM " . $table_insert . " where shopid=" . $data['id'] )->fetch();
	}

	$id_block_content = array();
	$sql = 'SELECT bid FROM ' . TABLE_SHOPS_NAME . '_block where id=' . $data['id'];
	$result = $db->query( $sql );

	while( list( $bid_i ) = $result->fetch( 3 ) )
	{
		$id_block_content[] = $bid_i;
	}
}

if( ! empty( $data['homeimgfile'] ) and file_exists( NV_UPLOADS_REAL_DIR . '/' . $module_name . '/' . $data['homeimgfile'] ) )
{
	$data['homeimgfile'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_name . '/' . $data['homeimgfile'];
}
$data['hometext'] = nv_htmlspecialchars( nv_br2nl( $data['hometext'] ) );

$tdate = date( 'H|i', $data['publtime'] );
$publ_date = date( 'd/m/Y', $data['publtime'] );
list( $phour, $pmin ) = explode( '|', $tdate );
if( $data['exptime'] == 0 )
{
	$emin = $ehour = 0;
	$exp_date = '';
}
else
{
	$exp_date = date( 'd/m/Y', $data['exptime'] );
	$tdate = date( 'H|i', $data['exptime'] );
	list( $ehour, $emin ) = explode( '|', $tdate );
}

if( ! empty( $data['otherimage'] ) )
{
	$otherimage = explode( '|', $data['otherimage'] );
}
else
{
	$otherimage = array();
}

$xtpl = new XTemplate( 'content.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file );
$xtpl->assign( 'LANG', $lang_module );
$xtpl->assign( 'data', $data );
$xtpl->assign( 'NV_BASE_ADMINURL', NV_BASE_ADMINURL );
$xtpl->assign( 'NV_NAME_VARIABLE', NV_NAME_VARIABLE );
$xtpl->assign( 'NV_OP_VARIABLE', NV_OP_VARIABLE );
$xtpl->assign( 'module_name', $module_name );
$xtpl->assign( 'CURRENT', NV_UPLOADS_DIR . '/' . $module_name . '/' . date( 'Y_m' ) );

if( $error != '' )
{
	$xtpl->assign( 'error', $error );
	$xtpl->parse( 'main.error' );
}

if( $data['status'] == 1 )
{
	$xtpl->parse( 'main.status' );
}
else
{
	$xtpl->parse( 'main.status0' );
}

// Other image
$items = 0;
if( ! empty( $otherimage ) )
{
	foreach( $otherimage as $otherimage_i )
	{
		if( ! empty( $otherimage_i ) and file_exists( NV_UPLOADS_REAL_DIR . '/' . $module_name . '/' . $otherimage_i ) )
		{
			$otherimage_i = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_name . '/' . $otherimage_i;
		}
		$data_otherimage_i = array( 'id' => $items, 'value' => $otherimage_i );
		$xtpl->assign( 'DATAOTHERIMAGE', $data_otherimage_i );
		$xtpl->parse( 'main.otherimage' );
		++$items;
	}
}
$xtpl->assign( 'FILE_ITEMS', $items );

foreach( $global_array_cat as $catid_i => $rowscat )
{
	$xtitle_i = '';
	if( $rowscat['lev'] > 0 )
	{
		for( $i = 1; $i <= $rowscat['lev']; $i++ )
		{
			$xtitle_i .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
	}
	$rowscat['title'] = $xtitle_i . $rowscat['title'];
	$rowscat['selected'] = ( $catid_i == $data['listcatid'] ) ? ' selected="selected"' : '';

	$xtpl->assign( 'ROWSCAT', $rowscat );
	$xtpl->parse( 'main.rowscat' );
}

// List group
//var_dump($data['group_id']); die;
if( ! empty( $data['group_id'] ) )
{
	$array_groupid_in_row = $data['group_id'];
}
else
{
	$array_groupid_in_row = array();
}

$inrow = nv_base64_encode( serialize( $array_groupid_in_row ) );
$xtpl->assign( 'url_load', NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=getgroup&cid=' . $data['listcatid'] . '&inrow=' . $inrow );
$xtpl->assign( 'inrow', $inrow );
$xtpl->parse( 'main.listgroup' );

// Time update
$xtpl->assign( 'publ_date', $publ_date );
$select = '';
for( $i = 0; $i <= 23; $i++ )
{
	$select .= "<option value=\"" . $i . "\"" . ( ( $i == $phour ) ? " selected=\"selected\"" : "" ) . ">" . str_pad( $i, 2, "0", STR_PAD_LEFT ) . "</option>\n";
}
$xtpl->assign( 'phour', $select );

$select = "";
for( $i = 0; $i < 60; $i++ )
{
	$select .= "<option value=\"" . $i . "\"" . ( ( $i == $pmin ) ? " selected=\"selected\"" : "" ) . ">" . str_pad( $i, 2, "0", STR_PAD_LEFT ) . "</option>\n";
}
$xtpl->assign( 'pmin', $select );

// Time exp
$xtpl->assign( 'exp_date', $exp_date );
$select = "";
for( $i = 0; $i <= 23; $i++ )
{
	$select .= "<option value=\"" . $i . "\"" . ( ( $i == $ehour ) ? " selected=\"selected\"" : "" ) . ">" . str_pad( $i, 2, "0", STR_PAD_LEFT ) . "</option>\n";
}
$xtpl->assign( 'ehour', $select );

$select = '';
for( $i = 0; $i < 60; $i++ )
{
	$select .= "<option value=\"" . $i . "\"" . ( ( $i == $emin ) ? " selected=\"selected\"" : "" ) . ">" . str_pad( $i, 2, "0", STR_PAD_LEFT ) . "</option>\n";
}
$xtpl->assign( 'emin', $select );

// Allowed comm
$allowed_comm = explode( ',', $data['allowed_comm'] );
foreach( $groups_list as $_group_id => $_title )
{
	$xtpl->assign( 'ALLOWED_COMM', array(
		'value' => $_group_id,
		'checked' => in_array( $_group_id, $allowed_comm ) ? ' checked="checked"' : '',
		'title' => $_title ) );
	$xtpl->parse( 'main.allowed_comm' );
}

$data['bodytext'] = htmlspecialchars( nv_editor_br2nl( $data['bodytext'] ) );
if( defined( 'NV_EDITOR' ) and function_exists( 'nv_aleditor' ) )
{
	$edits = nv_aleditor( 'bodytext', '100%', '300px', $data['bodytext'] );
}
else
{
	$edits = "<textarea style=\"width: 100%\" name=\"bodytext\" id=\"bodytext\" cols=\"20\" rows=\"15\">" . $data['bodytext'] . "</textarea>";
}

$shtm = '';
if( count( $array_block_cat_module ) > 0 )
{
	foreach( $array_block_cat_module as $bid_i => $bid_title )
	{
		$ch = in_array( $bid_i, $id_block_content ) ? " checked=\"checked\"" : "";
		$shtm .= "<input class=\"news_checkbox\" type=\"checkbox\" name=\"bids[]\" value=\"" . $bid_i . "\"" . $ch . ">" . $bid_title . "<br />\n";
	}
	$xtpl->assign( 'row_block', $shtm );
	$xtpl->parse( 'main.block_cat' );
}

// List discount
$sql = 'SELECT * FROM ' . TABLE_SHOPS_NAME . '_discounts';
$_result = $db->query( $sql );
while( $_discount = $_result->fetch() )
{
	$_discount['selected'] = ( $_discount['did'] == $data['discount_id'] ) ? "selected=\"selected\"" : "";
	$xtpl->assign( 'DISCOUNT', $_discount );
	$xtpl->parse( 'main.discount' );
}

// List pro_unit
$sql = 'SELECT id, ' . NV_LANG_DATA . '_title FROM ' . TABLE_SHOPS_NAME . '_units';
$result_unit = $db->query( $sql );
if( $result_unit->rowCount() == 0 )
{
	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=prounit' );
	die();
}

while( list( $unitid_i, $title_i ) = $result_unit->fetch( 3 ) )
{
	$xtpl->assign( 'utitle', $title_i );
	$xtpl->assign( 'uid', $unitid_i );
	$uch = ( $data['product_unit'] == $unitid_i ) ? "selected=\"selected\"" : "";
	$xtpl->assign( 'uch', $uch );
	$xtpl->parse( 'main.rowunit' );
}

// Print tags
if( ! empty( $data['keywords'] ) )
{
	$keywords_array = explode( ',', $data['keywords'] );
	foreach( $keywords_array as $keywords )
	{
		$xtpl->assign( 'KEYWORDS', $keywords );
		$xtpl->parse( 'main.keywords' );
	}
}

if( $module_config[$module_name]['auto_tags'] )
{
	$xtpl->parse( 'main.auto_tags' );
}

$archive_checked = ( $data['archive'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'archive_checked', $archive_checked );

$inhome_checked = ( $data['inhome'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'inhome_checked', $inhome_checked );

$vat_checked1 = ( $data['vat'] == 0 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checkvat1', $vat_checked1 );
$vat_checked2 = ( $data['vat'] == 1 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checkvat2', $vat_checked2 );

$checktype1 = ( $data['typeproduct'] == 0 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checktype1', $checktype1 );
$checktype2 = ( $data['typeproduct'] == 1 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checktype2', $checktype2 );

$checknew = ( $data['new_old'] == 0 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checknew', $checknew );
$checkold = ( $data['new_old'] == 1 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checkold', $checkold );

$checkadd = ( $data['adddefaul'] == 1 ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'checkadd', $checkadd );

$allowed_rating_checked = ( $data['allowed_rating'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'allowed_rating_checked', $allowed_rating_checked );

$allowed_send_checked = ( $data['allowed_send'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'allowed_send_checked', $allowed_send_checked );

$allowed_print_checked = ( $data['allowed_print'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'allowed_print_checked', $allowed_print_checked );

$allowed_save_checked = ( $data['allowed_save'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'allowed_save_checked', $allowed_save_checked );

$showprice_checked = ( $data['showprice'] ) ? " checked=\"checked\"" : "";
$xtpl->assign( 'ck_showprice', $showprice_checked );

if( ! empty( $money_config ) )
{
	foreach( $money_config as $code => $info )
	{
		$info['select'] = ( $data['money_unit'] == $code ) ? "selected=\"selected\"" : "";
		$xtpl->assign( 'MON', $info );
		$xtpl->parse( 'main.money_unit' );
	}
}

$xtpl->assign( 'edit_bodytext', $edits );

if( $data['id'] > 0 )
{
	$op = 'items';
	$xtpl->parse( 'main.edit' );
}
else
{
	$xtpl->parse( 'main.add' );
}

if( empty( $data['alias'] ) )
{
	$xtpl->parse( 'main.getalias' );
}
//tuybien xtpl
$xtpl->assign( 'ROW', $custom );
//print_r($custom);die;
if( $data['listcatid'] and ! empty( $global_array_cat[$data['listcatid']]['form'] ) )
{
	//$datacustom_form = nv_show_custom_form( $global_array_cat[$data['listcatid']]['form'], $array_custom, $array_custom_lang );
	$datacustom_form = nv_show_custom_form( $global_array_cat[$data['listcatid']]['form'], $custom, $array_custom_lang );
	$xtpl->assign( 'DATACUSTOM_FORM', $datacustom_form );
}
$xtpl->parse( 'main.custom_form' );

$xtpl->parse( 'main' );
$contents = $xtpl->text( 'main' );

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme( $contents );
include NV_ROOTDIR . '/includes/footer.php';
