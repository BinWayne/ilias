<#2949>
<?php
	// empty step
?>
<#2950>
<?php
	$ilDB->modifyTableColumn('table_properties', 'value',
		array("type" => "text", "length" => 4000, "notnull" => true));
?>
<#2951>
<?php
if(!$ilDB->tableExists('payment_paymethods'))
{
	$fields = array (
    'pm_id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
    'pm_title'   => array ('type' => 'text', 'notnull' => true, 'length' => 60, 'fixed' => false),
    'pm_enabled'    => array ('type' => 'integer', 'length'  => 1,"notnull" => true,"default" => 0),
    'save_usr_adr'  => array ('type' => 'integer', 'length'  => 1,"notnull" => true,"default" => 0)
  );
  $ilDB->createTable('payment_paymethods', $fields);
  $ilDB->addPrimaryKey('payment_paymethods', array('pm_id'));
// this is not necessary and triggers an error under oracle, since the index
// is already created with the primary key: (alex, 19.7.2010)
//  $ilDB->addIndex('payment_paymethods',array('pm_id'),'i1');
  $ilDB->createSequence("payment_paymethods");
}
?>
<#2952>
<?php
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_bill','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_bill = 1 : $pm_bill = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_bill','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $adr_bill = 1: $adr_bill = 0;

	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_bmf','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_bmf =1: $pm_bmf = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_bmf','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $adr_bmf = 1: $adr_bmf = 0;

	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_paypal','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_ppal =1: $pm_ppal = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_paypal','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $adr_ppal = 1: $adr_ppal = 0;

	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('pm_epay','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']== 1 ? $pm_epay = 1: $pm_epay = 0;
	$res = $ilDB->query("SELECT value FROM settings WHERE keyword = ".$ilDB->quote('save_user_adr_epay','text'));
	$row = $ilDB->fetchAssoc($res);
	$row['value']==1 ? $adr_epay = 1: $adr_epay = 0;

	$query = 'INSERT INTO payment_paymethods (pm_id, pm_title, pm_enabled, save_usr_adr) VALUES (%s, %s, %s, %s)';
	$types = array("integer", "text", "integer", "integer");

	$nextId = $ilDB->nextId('payment_paymethods');
	$bill_data = array($nextId, 'bill', $pm_bill, $adr_bill);
	$ilDB->manipulateF($query,$types,$bill_data);

	$nextId = $ilDB->nextId('payment_paymethods');
	$bmf_data  = array($nextId, 'bmf',  $pm_bmf, $adr_bmf);
	$ilDB->manipulateF($query,$types,$bmf_data);

	$nextId = $ilDB->nextId('payment_paymethods');
	$paypal_data  = array($nextId, 'paypal',  $pm_ppal, $adr_ppal);
	$ilDB->manipulateF($query,$types,$paypal_data);

	$nextId = $ilDB->nextId('payment_paymethods');
	$epay_data  = array($nextId, 'epay', $pm_epay, $adr_epay);
	  $ilDB->manipulateF($query,$types,$epay_data);
?>
<#2953>
<?php
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_bill', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_bmf', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_paypal', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('pm_epay', 'text'));

	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_bill', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_bmf', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_paypal', 'text'));
	$res = $ilDB->manipulate('DELETE FROM settings WHERE keyword = '.$ilDB->quote('save_user_adr_epay', 'text'));
?>
<#2954>
<?php
	if($ilDB->tableColumnExists("payment_settings", "hide_filtering"))
	{
		$ilDB->renameTableColumn('payment_settings', 'hide_filtering', 'objects_allow_custom_sorting');
	}
	$ilDB->modifyTableColumn('payment_settings', 'objects_allow_custom_sorting', array(
			"type" => "integer",
			'length' => 4,
			"notnull" => true,
			"default" => 0));
?>
<#2955>
<?php
	if (!$ilDB->tableColumnExists("payment_statistic", "currency_unit"))
	{
		$ilDB->addTableColumn("payment_statistic", "currency_unit", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 16,
		 	"fixed" => false));
	}
	$res = $ilDB->query('SELECT price from payment_statistic');
	$row = $ilDB->fetchAssoc($res);
	while($row = $ilDB->fetchAssoc($res))
	{
		$exp = explode(' ', $row['price']);
		$amount = $exp['0'];
		$currency = $exp['1'];

		$upd = $ilDB->manipulateF('UPDATE payment_statistic
			SET price = %s, currency_unit = %s',
		array('float','text'), array($amount, $currency));
	}
?>
<#2956>
<?php
if($ilDB->tableExists('payment_currencies'))
	$ilDB->dropTable('payment_currencies');
if($ilDB->tableExists('payment_currencies_seq'))
		$ilDB->dropTable('payment_currencies_seq');
?>
<#2957>
<?php
if(!$ilDB->tableExists('payment_currencies'))
{
	$fields = array (
    'currency_id'    => array(
    		'type' => 'integer',
    		'length'  => 4,
    		'notnull' => true,
    		'default' => 0),

    'unit'   => array(
    		'type' => 'text',
    		'notnull' => true,
    		'length' => 16,
    		'fixed' => false),

	'iso_code' => array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 4,
		 	"fixed" => false),

	'symbol' => array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 4,
		 	"fixed" => false),

	'conversion_rate' => array(
			"type" => "float",
			"notnull" => true,
			"default" => 0)
  );
  $ilDB->createTable('payment_currencies', $fields);
  $ilDB->addPrimaryKey('payment_currencies', array('currency_id'));
  $ilDB->createSequence("payment_currencies");
}
?>
<#2958>
<?php
	$res = $ilDB->query('SELECT currency_unit FROM payment_settings');
	$row = $ilDB->fetchAssoc($res);

	while($row = $ilDB->fetchAssoc($res))
	{
		$nextId = $ilDB->nextId('payment_currencies');
		$ins_res = $ilDB->manipulateF('INSERT INTO payment_currencies (currency_id, unit)
			VALUES(%s,%s)',
		array('integer', 'text'),
		array($nextId, $row['currency_unit']));

		$upd_prices = $ilDB->manipulateF('UPDATE payment_prices SET currency = %s',
		array('integer'), array($nextId));
		$ilDB->manipulateF('UPDATE payment_statistic SET currency_unit = %s', array('text'), array($row['currency_unit']));
	}
?>
<#2959>
<?php
	$res = $ilDB->query('SELECT * FROM payment_statistic');
	$statistic_arr = array();
	$counter = 0;
	while($row = $ilDB->fetchAssoc($res))
	{
		$statistic_arr[$counter]['booking_id'] = $row['booking_id'];
		$tmp_p = str_replace(",", ".", $row['price']);
		$pr = str_replace(' ', '', $tmp_p);
		$statistic_arr[$counter]['price'] = (float)$pr;
		$tmp_d =  str_replace(",", ".",  $row['discount']);
		$dis = str_replace(' ', '', $tmp_d);
		$statistic_arr[$counter]['discount'] =(float)$dis;
		$counter++;
	}
	
	$ilDB->manipulate('UPDATE payment_statistic SET
		price = NULL, discount = NULL');

			$ilDB->modifyTableColumn('payment_statistic', 'price', array(
			"type" => "float",
			"notnull" => true,
			"default" => 0));

	$ilDB->modifyTableColumn('payment_statistic', 'discount', array(
			"type" => "float",
			"notnull" => true,
			"default" => 0));

	foreach($statistic_arr as $stat)
	{
		$upd = $ilDB->manipulateF('UPDATE payment_statistic SET
			price = %s,
			discount = %s
		WHERE booking_id = %s',
		array('float', 'float','integer'),
		array($stat['price'],$stat['discount'], $stat['booking_id']));
	}
?>
<#2960>
<?php
	if (!$ilDB->tableColumnExists("payment_statistic", "email_extern"))
	{
		$ilDB->addTableColumn('payment_statistic', 'email_extern', array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 80,
		 	"fixed" => false));
	}
	if (!$ilDB->tableColumnExists("payment_statistic", "name_extern"))
	{
		$ilDB->addTableColumn('payment_statistic', 'name_extern', array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 80,
		 	"fixed" => false));
	}
?>
<#2961>
<?php
	if (!$ilDB->tableColumnExists("payment_shopping_cart", "session_id"))
	{
		$ilDB->addTableColumn('payment_shopping_cart', 'session_id', array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 80,
		 	"fixed" => false));
	}
?>
<#2962>
<?php
	if ($ilDB->tableExists('payment_bill_vendor'))
	{
		$ilDB->dropTable('payment_bill_vendor');
	}
?>
<#2963>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#2964>
<?php
	$ilDB->dropIndex('svy_category', 'i2');
?>
<#2965>
<?php
if($ilDB->tableColumnExists('svy_category','scale'))
{
	$ilDB->dropTableColumn('svy_category', 'scale');
}
?>
<#2966>
<?php
if($ilDB->tableColumnExists('svy_category','other'))
{
	$ilDB->dropTableColumn('svy_category', 'other');
}
?>
<#2967>
<?php
if(!$ilDB->tableColumnExists('svy_variable','other'))
{
  $ilDB->addTableColumn("svy_variable", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
}
?>
<#2968>
<?php
if($ilDB->tableColumnExists('svy_qst_mc','use_other_answer'))
{
	$ilDB->dropTableColumn('svy_qst_mc', 'use_other_answer');
}
?>
<#2969>
<?php
if($ilDB->tableColumnExists('svy_qst_sc','use_other_answer'))
{
	$ilDB->dropTableColumn('svy_qst_sc', 'use_other_answer');
}
?>
<#2970>
<?php
	// mail rcp_to
	$ilDB->addTableColumn("mail", "rcp_to_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET rcp_to_tmp = rcp_to');
	$ilDB->dropTableColumn('mail', 'rcp_to');
	$ilDB->renameTableColumn("mail", "rcp_to_tmp", "rcp_to");
?>
<#2971>
<?php
	// mail rcp_cc
	$ilDB->addTableColumn("mail", "rcp_cc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET rcp_cc_tmp = rcp_cc');
	$ilDB->dropTableColumn('mail', 'rcp_cc');
	$ilDB->renameTableColumn("mail", "rcp_cc_tmp", "rcp_cc");
?>
<#2972>
<?php
	// mail rcp_bcc
	$ilDB->addTableColumn("mail", "rcp_bcc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET rcp_bcc_tmp = rcp_bcc');
	$ilDB->dropTableColumn('mail', 'rcp_bcc');
	$ilDB->renameTableColumn("mail", "rcp_bcc_tmp", "rcp_bcc");
?>
<#2973>
<?php
	// mail_saved rcp_to
	$ilDB->addTableColumn("mail_saved", "rcp_to_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET rcp_to_tmp = rcp_to');
	$ilDB->dropTableColumn('mail_saved', 'rcp_to');
	$ilDB->renameTableColumn("mail_saved", "rcp_to_tmp", "rcp_to");
?>
<#2974>
<?php
	// mail_saved rcp_cc
	$ilDB->addTableColumn("mail_saved", "rcp_cc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET rcp_cc_tmp = rcp_cc');
	$ilDB->dropTableColumn('mail_saved', 'rcp_cc');
	$ilDB->renameTableColumn("mail_saved", "rcp_cc_tmp", "rcp_cc");
?>
<#2975>
<?php
	// mail_saved rcp_bcc
	$ilDB->addTableColumn("mail_saved", "rcp_bcc_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET rcp_bcc_tmp = rcp_bcc');
	$ilDB->dropTableColumn('mail_saved', 'rcp_bcc');
	$ilDB->renameTableColumn("mail_saved", "rcp_bcc_tmp", "rcp_bcc");
?>
<#2976>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#2977>
<?php
	$ilDB->addTableColumn("content_object", "public_scorm_file", array(
		"type" => "text",
		"notnull" => false,
		"length" => 50));
?>
<#2978>
<?php
	$query = 'UPDATE usr_pref SET value = ROUND(value / 60) WHERE keyword = %s AND value IS NOT NULL';
	if($ilDB->getDBType() == 'oracle')
	{
		$query .= " AND LENGTH(TRIM(TRANSLATE (value, ' +-.0123456789',' '))) IS NULL";
		$ilDB->manipulateF($query, array('text'), array('session_reminder_lead_time'));
	}
	else
	{
		$ilDB->manipulateF($query, array('text'), array('session_reminder_lead_time'));
	}
?>
<#2979>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#2980>
<?php

if(!$ilDB->tableExists('openid_provider'))
{
	$fields = array(
		'provider_id'	=> array(
			'type'		=> 'integer',
			'length'	=> 4,
		),
		'enabled' 		=> array(
			'type' 			=> 'integer',
			'length' 		=> 1,
		),
		'name' 			=> array(
			'type' 			=> 'text',
			'length' 		=> 128,
			'fixed'			=> false,
			'notnull'		=> false
		),
		'url'			=> array(
			'type'			=> 'text',
			'length'		=> 512,
			'fixed'			=> false,
			'notnull'		=> false
		),
		'image'			=> array(
			'type'			=> 'integer',
			'length'		=> 2
		)
	);
	$ilDB->createTable('openid_provider',$fields);
	$ilDB->addPrimaryKey('openid_provider',array('provider_id'));
	$ilDB->createSequence('openid_provider');
	
}
?>

<#2981>
<?php
$query = "INSERT INTO openid_provider (provider_id,enabled,name,url,image) ".
	"VALUES ( ".
	$ilDB->quote($ilDB->nextId('openid_provider'),'integer').','.
	$ilDB->quote(1,'integer').','.
	$ilDB->quote('MyOpenID','text').','.
	$ilDB->quote('http://%s.myopenid.com').','.
	$ilDB->quote(1,'integer').
	")";
$res = $ilDB->query($query);
?>
<#2982>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 0)
	{
		$ilDB->createTable("exc_assignment",
			array(
				"id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"exc_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"time_stamp" => array(
					"type" => "integer", "length" => 4, "notnull" => false
				),
				"instruction" => array(
					"type" => "clob"
				)
			)
		);

		$ilDB->addPrimaryKey("exc_assignment", array("id"));
		
		$ilDB->createSequence("exc_assignment");
	}
?>
<#2983>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 1)
	{
		$ilDB->createTable("exc_mem_ass_status",
			array(
				"ass_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"usr_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"notice" => array(
					"type" => "text", "length" => 4000, "notnull" => false
				),
				"returned" => array(
					"type" => "integer", "length" => 1, "notnull" => true, "default" => 0
				),
				"solved" => array(
					"type" => "integer", "length" => 1, "notnull" => false
				),
				"status_time" => array(
					"type" => "timestamp", "notnull" => false
				),
				"sent" => array(
					"type" => "integer", "length" => 1, "notnull" => false
				),
				"sent_time" => array(
					"type" => "timestamp", "notnull" => false
				),
				"feedback_time" => array(
					"type" => "timestamp", "notnull" => false
				),
				"feedback" => array(
					"type" => "integer", "length" => 1, "notnull" => true, "default" => 0
				),
				"status" => array(
					"type" => "text", "length" => 9, "fixed" => true, "default" => "notgraded", "notnull" => false
				)
			)
		);
			
		$ilDB->addPrimaryKey("exc_mem_ass_status", array("ass_id", "usr_id"));
	}
?>
<#2984>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 2)
	{
		$ilDB->addTableColumn("exc_returned",
			"ass_id",
			array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#2985>
<?php
	/*$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 3)
	{
		$ilDB->createTable("exc_mem_tut_status",
			array (
				"ass_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"mem_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"tut_id" => array(
					"type" => "integer", "length" => 4, "notnull" => true
				),
				"download_time" => array(
					"type" => "timestamp"
				)
			)
		);
	}*/
?>
<#2986>
<?php
	/*$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 4)
	{
		$ilDB->addPrimaryKey("exc_mem_tut_status", array("ass_id", "mem_id", "tut_id"));
	}*/
?>
<#2987>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 5)
	{
		$set = $ilDB->query("SELECT * FROM exc_data");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			// Create exc_assignment records for all existing exercises
			// -> instruction and time_stamp fields in exc_data are obsolete
			$next_id = $ilDB->nextId("exc_assignment");
			$ilDB->insert("exc_assignment", array(
				"id" => array("integer", $next_id),
				"exc_id" => array("integer", $rec["obj_id"]),
				"time_stamp" => array("integer", $rec["time_stamp"]),
				"instruction" => array("clob", $rec["instruction"])
				));
		}
	}
?>
<#2988>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 6)
	{
		$ilDB->addIndex("exc_members", array("obj_id"), "ob");
	}
?>
<#2989>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 7)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$set2 = $ilDB->query("SELECT * FROM exc_members ".
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
			while ($rec2  = $ilDB->fetchAssoc($set2))
			{
				$ilDB->manipulate("INSERT INTO exc_mem_ass_status ".
					"(ass_id, usr_id, notice, returned, solved, status_time, sent, sent_time,".
					"feedback_time, feedback, status) VALUES (".
					$ilDB->quote($rec["id"], "integer").",".
					$ilDB->quote($rec2["usr_id"], "integer").",".
					$ilDB->quote($rec2["notice"], "text").",".
					$ilDB->quote($rec2["returned"], "integer").",".
					$ilDB->quote($rec2["solved"], "integer").",".
					$ilDB->quote($rec2["status_time"], "timestamp").",".
					$ilDB->quote($rec2["sent"], "integer").",".
					$ilDB->quote($rec2["sent_time"], "timestamp").",".
					$ilDB->quote($rec2["feedback_time"], "timestamp").",".
					$ilDB->quote($rec2["feedback"], "integer").",".
					$ilDB->quote($rec2["status"], "text").
					")");
			}
		}
	}
?>
<#2990>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 8)
	{
		$ilDB->addIndex("exc_usr_tutor", array("obj_id"), "ob");
	}
?>
<#2991>
<?php
	/*$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 9)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$set2 = $ilDB->query("SELECT * FROM exc_usr_tutor ".
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
			while ($rec2  = $ilDB->fetchAssoc($set2))
			{
				$ilDB->manipulate("INSERT INTO exc_mem_tut_status ".
					"(ass_id, mem_id, tut_id, download_time) VALUES (".
					$ilDB->quote($rec["id"], "integer").",".
					$ilDB->quote($rec2["usr_id"], "integer").",".
					$ilDB->quote($rec2["tutor_id"], "integer").",".
					$ilDB->quote($rec2["download_time"], "timestamp").
					")");
			}
		}
	}*/
?>
<#2992>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 10)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$ilDB->manipulate("UPDATE exc_returned SET ".
				" ass_id = ".$ilDB->quote($rec["id"], "integer").
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
		}
	}
?>
<#2993>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 11)
	{
		$ilDB->addTableColumn("exc_assignment",
			"title",
			array("type" => "text", "length" => 200, "notnull" => false));

		$ilDB->addTableColumn("exc_assignment",
			"start_time",
			array("type" => "integer", "length" => 4, "notnull" => false));

		$ilDB->addTableColumn("exc_assignment",
			"mandatory",
			array("type" => "integer", "length" => 1, "notnull" => false, "default" => 0));
	}
?>
<#2994>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 12)
	{
		$ilDB->addTableColumn("exc_data",
			"pass_mode",
			array("type" => "text", "length" => 8, "fixed" => false,
				"notnull" => true, "default" => "all"));

		$ilDB->addTableColumn("exc_data",
			"pass_nr",
			array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#2995>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 13)
	{
		$ilDB->addTableColumn("exc_assignment",
			"order_nr",
			array("type" => "integer", "length" => 4, "notnull" => true, "default" => 0));
	}
?>
<#2996>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 14)
	{
		$ilDB->addTableColumn("exc_data",
			"show_submissions",
			array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
	}
?>
<#2997>
<?php
/*	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 15)
	{
			$new_ex_path = CLIENT_DATA_DIR."/ilExercise";
			
			$old_ex_path = CLIENT_DATA_DIR."/exercise";
			
			$old_ex_files = array();
			
			if (is_dir($old_ex_path))
			{
				$dh_old_ex_path = opendir($old_ex_path);
				
				// old exercise files into an assoc array to
				// avoid reading of all files each time
				
				while($file = readdir($dh_old_ex_path))
				{
					if(is_dir($old_ex_path."/".$file))
					{
						continue;
					}
					list($obj_id,$rest) = split('_',$file,2);
					$old_ex_files[$obj_id][] = array("full" => $file,
						"rest" => $rest);
				}
			}
//var_dump($old_ex_files);
			
			$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
			while ($rec  = $ilDB->fetchAssoc($set))
			{
				// move exercise files to assignment directories
				if (is_array($old_ex_files[$rec["exc_id"]]))
				{
					foreach ($old_ex_files[$rec["exc_id"]] as $file)
					{
						$old = $old_ex_path."/".$file["full"];
						$new = $new_ex_path."/".$this->createPathFromId($rec["exc_id"], "exc").
							"/ass_".$rec["id"]."/".$file["rest"];
							
						if (is_file($old))
						{
							ilUtil::makeDirParents(dirname($new));
							rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
						}
					}
				}

				// move submitted files to assignment directories
				if (is_dir($old_ex_path."/".$rec["exc_id"]))
				{
					$old = $old_ex_path."/".$rec["exc_id"];
					$new = $new_ex_path."/".$this->createPathFromId($rec["exc_id"], "exc").
						"/subm_".$rec["id"];
					ilUtil::makeDirParents(dirname($new));
					rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
				}
				
	}*/
?>
<#2998>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 16)
	{
		$ilDB->addTableColumn("exc_usr_tutor",
			"ass_id",
			array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#2999>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 17)
	{
		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$ilDB->manipulate("UPDATE exc_usr_tutor SET ".
				" ass_id = ".$ilDB->quote($rec["id"], "integer").
				" WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer")
				);
		}
		$ilDB->manipulate("DELETE FROM exc_usr_tutor WHERE ass_id IS NULL");
	}
?>
<#3000>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 18)
	{
		$ilCtrlStructureReader->getStructure();
	}
?>
<#3001>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 19)
	{
		$ilDB->addTableColumn("exc_mem_ass_status",
			"mark",
			array("type" => "text", "length" => 32, "notnull" => false));
		$ilDB->addTableColumn("exc_mem_ass_status",
			"u_comment",
			array("type" => "text", "length" => 4000, "notnull" => false));

		$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			$set2 = $ilDB->query("SELECT * FROM ut_lp_marks WHERE obj_id = ".$ilDB->quote($rec["exc_id"], "integer"));
			while ($rec2 = $ilDB->fetchAssoc($set2))
			{
				$set3 = $ilDB->query("SELECT ass_id FROM exc_mem_ass_status WHERE ".
					"ass_id = ".$ilDB->quote($rec["id"], "integer").
					" AND usr_id = ".$ilDB->quote($rec2["usr_id"], "integer"));
				if ($rec3 = $ilDB->fetchAssoc($set3))
				{
					$ilDB->manipulate("UPDATE exc_mem_ass_status SET ".
						" mark = ".$ilDB->quote($rec2["mark"], "text").",".
						" u_comment = ".$ilDB->quote($rec2["u_comment"], "text").
						" WHERE ass_id = ".$ilDB->quote($rec["id"], "integer").
						" AND usr_id = ".$ilDB->quote($rec2["usr_id"], "integer")
						);
				}
				else
				{
					$ilDB->manipulate("INSERT INTO exc_mem_ass_status (ass_id, usr_id, mark, u_comment) VALUES (".
						$ilDB->quote($rec["id"], "integer").", ".
						$ilDB->quote($rec2["usr_id"], "integer").", ".
						$ilDB->quote($rec2["mark"], "text").", ".
						$ilDB->quote($rec2["u_comment"], "text").")"
						);
				}
			}
		}
	}
?>
<#3002>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 20)
	{
		$ilDB->dropPrimaryKey("exc_usr_tutor");
		$ilDB->addPrimaryKey("exc_usr_tutor",
			array("ass_id", "usr_id", "tutor_id"));
	}
?>
<#3003>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 23)
	{
		$ilDB->modifyTableColumn("exc_assignment",
			"mandatory",
			array("type" => "integer", "length" => 1, "notnull" => false, "default" => 1));
		$ilDB->manipulate("UPDATE exc_assignment SET ".
			" mandatory = ".$ilDB->quote(1, "integer"));
		
		$set = $ilDB->query("SELECT e.id, e.exc_id, o.title, e.title t2 FROM exc_assignment e JOIN object_data o".
							" ON (e.exc_id = o.obj_id)");
		while ($rec  = $ilDB->fetchAssoc($set))
		{
			if ($rec["t2"] == "")
			{
				$ilDB->manipulate("UPDATE exc_assignment SET ".
					" title = ".$ilDB->quote($rec["title"], "text")." ".
					"WHERE id = ".$ilDB->quote($rec["id"], "text"));
			}
		}
	}
?>
<#3004>
<?php
	$setting = new ilSetting();
	$st_step = (int) $setting->get('patch_stex_db');
	if ($st_step <= 15)
	{
			include_once("./Services/Migration/DBUpdate_3004/classes/class.ilDBUpdate3004.php");
		
			$new_ex_path = CLIENT_DATA_DIR."/ilExercise";
			
			$old_ex_path = CLIENT_DATA_DIR."/exercise";
			
			$old_ex_files = array();
			
			if (is_dir($old_ex_path))
			{
				$dh_old_ex_path = opendir($old_ex_path);
				
				// old exercise files into an assoc array to
				// avoid reading of all files each time
				
				while($file = readdir($dh_old_ex_path))
				{
					if(is_dir($old_ex_path."/".$file))
					{
						continue;
					}
					list($obj_id,$rest) = split('_',$file,2);
					$old_ex_files[$obj_id][] = array("full" => $file,
						"rest" => $rest);
				}
			}

//var_dump($old_ex_files);
			
			$set = $ilDB->query("SELECT id, exc_id FROM exc_assignment");
			while ($rec  = $ilDB->fetchAssoc($set))
			{
				// move exercise files to assignment directories
				if (is_array($old_ex_files[$rec["exc_id"]]))
				{
					foreach ($old_ex_files[$rec["exc_id"]] as $file)
					{
						$old = $old_ex_path."/".$file["full"];
						$new = $new_ex_path."/".ilDBUpdate3004::createPathFromId($rec["exc_id"], "exc").
							"/ass_".$rec["id"]."/".$file["rest"];
							
						if (is_file($old))
						{
							ilUtil::makeDirParents(dirname($new));
							rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
						}
					}
				}

				// move submitted files to assignment directories
				if (is_dir($old_ex_path."/".$rec["exc_id"]))
				{
					$old = $old_ex_path."/".$rec["exc_id"];
					$new = $new_ex_path."/".ilDBUpdate3004::createPathFromId($rec["exc_id"], "exc").
						"/subm_".$rec["id"];
					ilUtil::makeDirParents(dirname($new));
					rename($old, $new);
//echo "<br><br>move: ".$old.
//	"<br>to: ".$new;
				}
				
			}
	}

?>
<#3005>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail rcp_to
		if (!$ilDB->tableColumnExists('mail', 'rcp_to_tmp'))
		{
			$ilDB->addTableColumn("mail", "rcp_to_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3006>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail SET rcp_to_tmp = rcp_to');
	}
?>
<#3007>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail', 'rcp_to'))
			$ilDB->dropTableColumn('mail', 'rcp_to');

		$ilDB->renameTableColumn("mail", "rcp_to_tmp", "rcp_to");
	}
?>
<#3008>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail rcp_cc
		if (!$ilDB->tableColumnExists('mail', 'rcp_cc_tmp'))
		{
			$ilDB->addTableColumn("mail", "rcp_cc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3009>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail SET rcp_cc_tmp = rcp_cc');
	}
?>
<#3010>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail', 'rcp_cc'))
			$ilDB->dropTableColumn('mail', 'rcp_cc');

		$ilDB->renameTableColumn("mail", "rcp_cc_tmp", "rcp_cc");
	}
?>
<#3011>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail rcp_bcc
		if (!$ilDB->tableColumnExists('mail', 'rcp_bcc_tmp'))
		{
			$ilDB->addTableColumn("mail", "rcp_bcc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3012>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail SET rcp_bcc_tmp = rcp_bcc');
	}
?>
<#3013>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if($ilDB->tableColumnExists('mail', 'rcp_bcc'))
			$ilDB->dropTableColumn('mail', 'rcp_bcc');

		$ilDB->renameTableColumn("mail", "rcp_bcc_tmp", "rcp_bcc");
	}
?>
<#3014>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail_saved rcp_to
		if (!$ilDB->tableColumnExists('mail_saved', 'rcp_to_tmp'))
		{
			$ilDB->addTableColumn("mail_saved", "rcp_to_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3015>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail_saved SET rcp_to_tmp = rcp_to');
	}
?>
<#3016>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail_saved', 'rcp_to'))
			$ilDB->dropTableColumn('mail_saved', 'rcp_to');

		$ilDB->renameTableColumn("mail_saved", "rcp_to_tmp", "rcp_to");
	}
?>
<#3017>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail_saved rcp_cc
		if (!$ilDB->tableColumnExists('mail_saved', 'rcp_cc_tmp'))
		{
			$ilDB->addTableColumn("mail_saved", "rcp_cc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3018>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail_saved SET rcp_cc_tmp = rcp_cc');
	}
?>
<#3019>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail_saved', 'rcp_cc'))
			$ilDB->dropTableColumn('mail_saved', 'rcp_cc');

		$ilDB->renameTableColumn("mail_saved", "rcp_cc_tmp", "rcp_cc");
	}
?>
<#3020>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// mail_saved rcp_bcc
		if (!$ilDB->tableColumnExists('mail_saved', 'rcp_bcc_tmp'))
		{
			$ilDB->addTableColumn("mail_saved", "rcp_bcc_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3021>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE mail_saved SET rcp_bcc_tmp = rcp_bcc');
	}
?>
<#3022>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('mail_saved', 'rcp_bcc'))
			$ilDB->dropTableColumn('mail_saved', 'rcp_bcc');

		$ilDB->renameTableColumn("mail_saved", "rcp_bcc_tmp", "rcp_bcc");
	}
?>
<#3023>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail','rcp_to', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3024>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail','rcp_cc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3025>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail','rcp_bcc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3026>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail_saved','rcp_to', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3027>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		 $ilDB->modifyTableColumn('mail_saved','rcp_cc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3028>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		$ilDB->modifyTableColumn('mail_saved','rcp_bcc', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3029>
<?php
	if(!$ilDB->tableColumnExists('usr_session', 'type'))
	{
		$ilDB->addTableColumn(
			'usr_session',
			'type',
			array(
				"type" => "integer",
				"notnull" => false,
				"length" => 4,
				"default" => null
			)
		);
	}
	if(!$ilDB->tableColumnExists('usr_session', 'createtime'))
	{
		$ilDB->addTableColumn(
			'usr_session',
			'createtime',
			array(
				"type" => "integer",
				"notnull" => false,
				"length" => 4,
				"default" => null
			)
		);
	}
 ?>
<#3030>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#3031>
<?php
	// new permission
	$new_ops_id = $ilDB->nextId('rbac_operations');

	$res = $ilDB->manipulatef('
		INSERT INTO rbac_operations (ops_id, operation, description, class)
	 	VALUES(%s, %s, %s, %s)',
	array('integer','text', 'text', 'text'),
	array($new_ops_id, 'mail_to_global_roles','User may send mails to global roles','object'));

	$res = $ilDB->queryF('SELECT obj_id FROM object_data WHERE type = %s AND title = %s',
	array('text', 'text'), array('typ', 'mail'));
	$row = $ilDB->fetchAssoc($res);

	$typ_id = $row['obj_id'];

	$query = $ilDB->manipulateF('INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
	array('integer','integer'), array($typ_id, $new_ops_id));
?>
<#3032>
<?php
	$query = 'SELECT ref_id FROM object_data '
		   . 'INNER JOIN object_reference ON object_reference.obj_id = object_data.obj_id '
		   . 'WHERE type = '.$ilDB->quote('mail', 'text');
	$res = $ilDB->query($query);
	$ref_ids = array();
	while($row = $ilDB->fetchAssoc($res))
	{
		$ref_ids[] = $row['ref_id'];
	}

	$query = 'SELECT rol_id FROM rbac_fa '
		   . 'WHERE assign = '.$ilDB->quote('y', 'text').' '
		   . 'AND parent = '.$ilDB->quote(ROLE_FOLDER_ID, 'integer');
	$res = $ilDB->query($query);
	$global_roles = array();
	while($row = $ilDB->fetchAssoc($res))
	{
		$global_roles[] = $row['rol_id'];
	}

	$query = 'SELECT ops_id FROM rbac_operations '
	       . 'WHERE operation = '.$ilDB->quote('mail_to_global_roles', 'text');
	$res = $ilDB->query($query);
	$data = $ilDB->fetchAssoc($res);
	$mtgr_permission = array();
	if((int)$data['ops_id'])
		$mtgr_permission[] = $data['ops_id'];

	foreach($global_roles as $role)
	{
		if($role == SYSTEM_ROLE_ID)
		{
			continue;
		}

		foreach($ref_ids as $ref_id)
		{
			$query = 'SELECT ops_id FROM rbac_pa '
			       . 'WHERE rol_id = '.$ilDB->quote($role, 'integer').' '
				   . 'AND ref_id = '.$ilDB->quote($ref_id, 'integer');
			$res = $ilDB->query($query);
			$operations = array();
			while($row = $ilDB->fetchAssoc($res))
			{
				$operations = unserialize($row['ops_id']);
			}
			if(!is_array($operations)) $operations = array();

			$permissions = array_unique(array_merge($operations, $mtgr_permission));

			// convert all values to integer
			foreach($permissions as $key => $operation)
			{
				$permissions[$key] = (int)$operation;
			}

			// Serialization des ops_id Arrays
			$ops_ids = serialize($permissions);

			$query = 'DELETE FROM rbac_pa '
			       . 'WHERE rol_id = %s '
			       . 'AND ref_id = %s';
			$res = $ilDB->queryF(
				$query, array('integer', 'integer'),
				array($role, $ref_id)
			);
			
			if(!count($permissions))
			{
				continue;
			}

			$ilDB->insert('rbac_pa',
				array(
					'rol_id' => array('integer', $role),
					'ops_id' => array('text', $ops_ids),
					'ref_id' => array('integer', $ref_id)
				)
			);
		}
	}
?>
<#3033>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3034>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3035>
<?php
	$ilDB->addTableColumn("ut_lp_marks", "status", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
	$ilDB->addTableColumn("ut_lp_marks", "status_changed", array(
		"type" => "timestamp",
		"notnull" => false));
	$ilDB->addTableColumn("ut_lp_marks", "status_dirty", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
	$ilDB->addTableColumn("ut_lp_marks", "percentage", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3036>
<?php
	$ilDB->addIndex('mail_obj_data',array('user_id','m_type'),'i1');
?>
<#3037>
<?php
$ilDB->addTableColumn('read_event', 'childs_read_count', array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0));
$ilDB->addTableColumn('read_event', 'childs_spent_seconds', array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0));
?>
<#3038>
<?php
  $ilDB->addIndex('addressbook',array('user_id','login','firstname','lastname'),'i1');
?>
<#3039>
<?php
	$ilDB->addIndex('mail',array('sender_id','user_id'), 'i4');
?>
<#3040>
<?php
		$ilDB->addTableColumn("frm_settings", "new_post_title", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3041>
<?php
// register new object type 'frma' for forum administration
 $id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "typ", "frma", "Forum administration", -1, ilUtil::now(), ilUtil::now()));
$typ_id = $id;

// create object data entry
$id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "frma", "__ForumAdministration", "Forum Administration", -1, ilUtil::now(), ilUtil::now()));

// create object reference entry
$ref_id = $ilDB->nextId('object_reference');
$res = $ilDB->manipulateF("INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($ref_id, $id));

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($ref_id, SYSTEM_FOLDER_ID);

// add rbac operations
// 1: edit_permissions, 2: visible, 3: read, 4:write
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 1));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 2));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 3));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 4));
?>
<#3042>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3043>
<?php
if(!$ilDB->tableExists('reg_registration_codes'))
{
	$fields = array (
		'code_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0),

		'code' => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 50,
			'fixed' => false),

	 	'role' => array(
			'type' => 'integer',
			'notnull' => false,
			'length' => 4,
			'default' => 0),

		'generated' => array(
			'type' => 'integer',
			'notnull' => false,
			'length' => 4,
			'default' => 0),

		'used' => array(
			'type' => 'integer',
			'notnull' => true,
			'length' => 4,
			'default' => 0)
	);
	$ilDB->createTable('reg_registration_codes', $fields);
	$ilDB->addPrimaryKey('reg_registration_codes', array('code_id'));
	$ilDB->addIndex('reg_registration_codes', array('code'), 'i1');
	$ilDB->createSequence("reg_registration_codes");
}
?>
<#3044>
<?php
	$ilDB->update("settings", array("value"=>array("integer", 0)), array("module"=>array("text", "common"), "keyword"=>array("text", "usr_settings_visib_reg_birthday")));
	$ilDB->update("settings", array("value"=>array("integer", 0)), array("module"=>array("text", "common"), "keyword"=>array("text", "usr_settings_visib_reg_instant_messengers")));
?>
<#3045>
<?php
if(!$ilDB->tableExists('org_unit_data'))
{
	$ilDB->createTable('org_unit_data', array(
			'ou_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'ou_title' => array(
				'type'     => 'text',
				'length'   => 128,
				'notnull' => false,
				'default' => null
			),
			'ou_subtitle' => array(
				'type'     => 'text',
				'length'   => 128,
				'notnull' => false,
				'default' => null
			),
			'ou_import_id' => array(
				'type'     => 'text',
				'length'   => 64,
				'notnull' => false,
				'default' => null
			)
	));
	$ilDB->addPrimaryKey('org_unit_data', array('ou_id'));
	$ilDB->createSequence('org_unit_data');
	$root_unit_id = $ilDB->nextId('org_unit_data');
	$ilDB->insert('org_unit_data', array(
		'ou_id' => array('integer', $root_unit_id),
		'ou_title' => array('text', 'RootUnit')
	));
}

if(!$ilDB->tableExists('org_unit_tree'))
{
	$ilDB->createTable('org_unit_tree', array(
			'tree' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'child' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'parent' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'lft' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'rgt' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'depth' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			)
	));
	$ilDB->addPrimaryKey('org_unit_tree', array('tree', 'child'));
	$ilDB->insert('org_unit_tree', array(
		'tree' => array('integer', 1),
		'child' => array('integer', $root_unit_id),
		'parent' => array('integer', 0),
		'lft' => array('integer', 1),
		'rgt' => array('integer', 2),
		'depth' => array('integer', 1)
	));
}

if(!$ilDB->tableExists('org_unit_assignments'))
{
	$ilDB->createTable('org_unit_assignments', array(
			'oa_ou_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_usr_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_reporting_access' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_cc_compl_invit' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_cc_compl_not1' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			),
			'oa_cc_compl_not2' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true,
				'default' => 0
			)
	));
	$ilDB->addPrimaryKey('org_unit_assignments', array('oa_ou_id', 'oa_usr_id'));
}

?>

<#3046>
<?php
	$ilDB->modifyTableColumn('glossary_definition', 'short_text',
		array("type" => "text", "length" => 4000, "notnull" => false));
?>

<#3047>
<?php
$ilDB->addTableColumn('glossary', 'pres_mode', array(
	"type" => "text",
	"notnull" => true,
	"length" => 10,
	"default" => "table"));
?>
<#3048>
<?php
$ilDB->addTableColumn('glossary', 'snippet_length', array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 200));
?>

<#3049>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3050>
<?php
$ilDB->addTableColumn("glossary_definition", "short_text_dirty", array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0
	));
?>

<#3051>
<?php
if(!$ilDB->tableExists('table_templates'))
{
	$ilDB->createTable('table_templates', array(
			'name' => array(
				'type'     => 'text',
				'length'   => 64,
				'notnull' => true
			),
			'user_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true
			),
			'context' => array(
				'type'     => 'text',
				'length'   => 128,
				'notnull' => true
			),
			'value' => array(
				'type'     => 'clob'
			)
	));
	$ilDB->addPrimaryKey('table_templates', array('name', 'user_id', 'context'));
}
?>

<#3052>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3053>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3054>
<?php
$ilDB->addTableColumn('frm_settings', 'notification_type', array(
	"type" => "text",
	"notnull" => true,
	"length" => 10,
	"default" => "all_users"));
?>
<#3055>
<?php
	$ilDB->addTableColumn("udf_definition", "visible_lua", array(
		"type" => "integer",
		"length" => 1,
		"notnull" => true,
		"default" => 0
	));
	$ilDB->addTableColumn("udf_definition", "changeable_lua", array(
		"type" => "integer",
		"length" => 1,
		"notnull" => true,
		"default" => 0
	));
?>
<#3056>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3057>
<?php
	$ilDB->addTableColumn("benchmark", "sql_stmt", array(
		"type" => "clob"
	));
?>
<#3058>
<?php
	$ilDB->dropTable("ut_lp_filter", false);
?>
<#3059>
<?php
	$ilDB->modifyTableColumn("exc_mem_ass_status",
		"u_comment",
		array("type" => "text", "length" => 4000, "notnull" => false));
?>
<#3060>
<?php
	// mail attachments
	$ilDB->addTableColumn("mail", "attachments_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail SET attachments_tmp = attachments');
	$ilDB->dropTableColumn('mail', 'attachments');
	$ilDB->renameTableColumn("mail", "attachments_tmp", "attachments");
?>
<#3061>
<?php
	// mail_saved attachments
	$ilDB->addTableColumn("mail_saved", "attachments_tmp", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE mail_saved SET attachments_tmp = attachments');
	$ilDB->dropTableColumn('mail_saved', 'attachments');
	$ilDB->renameTableColumn("mail_saved", "attachments_tmp", "attachments");
?>
<#3062>
<?php
	$ilDB->addTableColumn(
		'usr_search',
		'item_filter',
		array(
			'type' => 'text',
			'length' => 1000,
			'notnull' => false,
			'default'	=> NULL
		)
	);
?>
<#3063>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// test sequence
		if (!$ilDB->tableColumnExists('tst_sequence', 'sequence_tmp'))
		{
			$ilDB->addTableColumn("tst_sequence", "sequence_tmp", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3064>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE tst_sequence SET sequence_tmp = sequence');
	}
?>
<#3065>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('tst_sequence', 'sequence'))
			$ilDB->dropTableColumn('tst_sequence', 'sequence');

		$ilDB->renameTableColumn("tst_sequence", "sequence_tmp", "sequence");
	}
?>
<#3066>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		$ilDB->modifyTableColumn('tst_sequence','sequence', array("type" => "clob", "default" => null, "notnull" => false));
	}
?>
<#3067>
<?php
	$ilDB->modifyTableColumn('ut_lp_marks','u_comment',array('type' => 'text', 'default' => null, 'length' => 4000, 'notnull' => false));
?>
<#3068>
<?php
	$ilDB->addTableColumn('udf_definition','group_export',array('type' => 'integer','default' => 0,'length' => 1, 'notnull' => false));
?>
<#3069>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3070>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 200)),
		array('operation'=>array('text','mail_visible')));
?>
<#3071>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 210)),
		array('operation'=>array('text','smtp_mail')));
?>
<#3072>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 220)),
		array('operation'=>array('text','system_message')));
?>
<#3073>
<?php
	$ilDB->update('rbac_operations', array('op_order'=>array('integer', 230)),
		array('operation'=>array('text','mail_to_global_roles')));
?>
<#3074>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3075>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3076>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3077>
<?php
if(!$ilDB->tableExists('notification'))
{
	$ilDB->createTable('notification', array(
			'type' => array(
				'type'     => 'integer',
				'length'   => 1,
				'notnull' => true
			),
			'id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true
			),
			'user_id' => array(
				'type'     => 'integer',
				'length'   => 4,
				'notnull' => true
			),
			'last_mail' => array(
				'type'     => 'timestamp',
				'notnull'	=> false
			)
	));
	$ilDB->addPrimaryKey('notification', array('type', 'id', 'user_id'));
}
?>
<#3078>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3079>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3080>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3081>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3082>
<?php
	$ilDB->createTable('cal_rec_exclusion',array(
		'excl_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'cal_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'excl_date'	=> array(
			'type'	=> 'date',
			'notnull' => FALSE,
		)
	));
	
	$ilDB->addPrimaryKey('cal_rec_exclusion',array('excl_id'));
	$ilDB->addIndex('cal_rec_exclusion',array('cal_id'),'i1');
	$ilDB->createSequence('cal_rec_exclusion');
?>
<#3083>
<?php

// new permission
$new_ops_id = $ilDB->nextId('rbac_operations');
$query = "INSERT INTO rbac_operations (operation,description,class,op_order) ".
	"VALUES( ".
	$ilDB->quote('add_consultation_hours','text').', '.
	$ilDB->quote('Add Consultation Hours Calendar','text').", ".
	$ilDB->quote('object','text').", ".
	$ilDB->quote(300,'integer').
	")";
$res = $ilDB->query($query);

// Calendar settings
$query = "SELECT obj_id FROM object_data WHERE type = 'typ' AND title = 'cals' ";
$res = $ilDB->query($query);
$row = $res->fetchRow();
$cals = $row[0];



$ilDB->manipulateF(
	'INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
	array('integer','integer'), 
	array($cals, $new_ops_id));

?>
<#3084>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3085>
<?php
	$ilDB->createTable('rbac_log',array(
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'created'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'ref_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'action'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE
		),
		'data'	=> array(
			'type'	=> 'text',
			'length' => 4000
		)
	));
	$ilDB->addIndex('rbac_log',array('ref_id'),'i1');
?>
<#3086>
<?php
	$ilDB->createTable('booking_entry',array(
		'booking_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 256,
			'notnull' => FALSE
		),
		'description'	=> array(
			'type'	=> 'text',
			'length'=> 4000,
			'notnull' => FALSE
		),
		'location'	=> array(
			'type'	=> 'text',
			'length' => 512,
			'notnull' => FALSE
		),
		'deadline'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => TRUE
		),
		'num_bookings'	=> array(
			'type'	=> 'integer',
			'length' => 4,
			'notnull' => TRUE
		)
	));
	$ilDB->addPrimaryKey('booking_entry',array('booking_id'));
	$ilDB->createSequence('booking_entry');
?>
<#3087>
<?php
	$ilCtrlStructureReader->getStructure();
?>
?>
<#3088>
<?php
	$ilDB->createTable('page_qst_answer',array(
		'qst_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => TRUE
		),
		'try'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE
		),
		'passed'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => TRUE
		),
		'points'	=> array(
			'type'	=> 'float',
			'notnull' => TRUE
		)
	));
	$ilDB->addPrimaryKey('page_qst_answer', array('qst_id', 'user_id'));

?>
<#3089>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3090>
<?php
if($ilDB->tableColumnExists('booking_entry','title'))
{
	$ilDB->dropTableColumn('booking_entry', 'title');
}
if($ilDB->tableColumnExists('booking_entry','description'))
{
	$ilDB->dropTableColumn('booking_entry', 'description');
}
if($ilDB->tableColumnExists('booking_entry','location'))
{
	$ilDB->dropTableColumn('booking_entry', 'location');
}
?>
<#3091>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3092>
<?php
$fields = array (
    'id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
    'user_id'   => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
	'order_nr'   => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
    'title'    => array ('type' => 'text', 'length' => 200)
);

$ilDB->createTable('usr_ext_profile_page', $fields);
$ilDB->addPrimaryKey('usr_ext_profile_page', array('id'));
$ilDB->createSequence("usr_ext_profile_page");

?>
<#3093>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3094>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3095>
<?php
if(!$ilDB->tableExists('booking_user'))
{
	$ilDB->createTable('booking_user',array(
		'entry_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'tstamp'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_user', array('entry_id', 'user_id'));
}
?>
<#3096>
<?php
	if(!$ilDB->tableColumnExists('crs_settings','reg_ac_enabled'))
	{
		
		$ilDB->addTableColumn('crs_settings','reg_ac_enabled',array(
			'type'		=> 'integer',
			'notnull'	=> true,
			'length'	=> 1,
			'default'	=> 0
		));
	
		$ilDB->addTableColumn('crs_settings','reg_ac',array(
			'type'		=> 'text',
			'notnull'	=> false,
			'length'	=> 32
		));
	}
?>

<#3097>
<?php
	if(!$ilDB->tableColumnExists('grp_settings','reg_ac_enabled'))
	{
		$ilDB->addTableColumn('grp_settings','reg_ac_enabled',array(
			'type'		=> 'integer',
			'notnull'	=> true,
			'length'	=> 1,
			'default'	=> 0
		));
	
		$ilDB->addTableColumn('grp_settings','reg_ac',array(
			'type'		=> 'text',
			'notnull'	=> false,
			'length'	=> 32
		));
	}
?>
<#3098>
<?php
	if($ilDB->tableColumnExists("frm_settings", "new_post_title"))
	{
		$ilDB->renameTableColumn('frm_settings', 'new_post_title', 'preset_subject');
	}
?>
<#3099>
<?php
		$ilDB->addTableColumn("frm_settings", "add_re_subject", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3100>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3101>
<?php
	$ilDB->addTableColumn('il_object_def','export', array(
		'type'	=> 'integer',
		'notnull' => true,
		'length' => 1,
		'default' => 0
	));
?>
<#3102>
<?php
if(!$ilDB->tableExists('booking_type'))
{
	$ilDB->createTable('booking_type',array(
		'booking_type_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 255,
			'notnull' => true
		),
		'pool_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_type', array('booking_type_id'));
	$ilDB->createSequence('booking_type');
}
?>
<#3103>
<?php
if(!$ilDB->tableExists('export_file_info'))
{
	$ilDB->createTable('export_file_info',array(
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'export_type'	=> array(
			'type'	=> 'text',
			'length'=> 32,
			'notnull' => false
		),
		'file_name'	=> array(
			'type'	=> 'text',
			'length'=> 64,
			'notnull' => false
		),
		'version'	=> array(
			'type'	=> 'text',
			'length'=> 16,
			'notnull' => false
		),
		'create_date'	=> array(
			'type'	=> 'timestamp',
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('export_file_info', array('obj_id','export_type','file_name'));
	$ilDB->addIndex('export_file_info',array('create_date'),'i1');
}
?>
<#3104>
<?php
if(!$ilDB->tableExists('export_options'))
{
	$ilDB->createTable('export_options',array(
		'export_id'	=> array(
			'type'	=> 'integer',
			'length'=> 2,
			'notnull' => true
		),
		'keyword'	=> array(
			'type'	=> 'integer',
			'length'=> 2,
			'notnull' => true
		),
		'ref_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'obj_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'value'	=> array(
			'type'	=> 'text',
			'length' => 32,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('export_options', array('export_id','keyword','ref_id'));
}
?>
<#3105>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3106>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3107>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_mc','use_other_answer'))
	{
	  $ilDB->addTableColumn("svy_qst_mc", "use_other_answer", array("type" => "integer", "length" => 2, "notnull" => false));
	  $ilDB->addTableColumn("svy_qst_mc", "other_answer_label", array("type" => "text", "length" => 255, "notnull" => false, "default" => null));
	}
?>
<#3108>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_sc','use_other_answer'))
	{
	  $ilDB->addTableColumn("svy_qst_sc", "use_other_answer", array("type" => "integer", "length" => 2, "notnull" => false));
	  $ilDB->addTableColumn("svy_qst_sc", "other_answer_label", array("type" => "text", "length" => 255, "notnull" => false, "default" => null));
	}
?>
<#3109>
<?php
	if(!$ilDB->tableColumnExists('svy_category','scale'))
	{
	  $ilDB->addTableColumn("svy_category", "scale", array("type" => "integer", "length" => 4, "notnull" => false, "default" => null));
	}
?>
<#3110>
<?php
	if(!$ilDB->tableColumnExists('svy_category','other'))
	{
	  $ilDB->addTableColumn("svy_category", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
	}
?>
<#3111>
<?php
	if($ilDB->tableColumnExists('svy_qst_mc','other_answer_label'))
	{
		$ilDB->dropTableColumn('svy_qst_mc', 'other_answer_label');
	}
?>
<#3112>
<?php
	if($ilDB->tableColumnExists('svy_qst_sc','other_answer_label'))
	{
		$ilDB->dropTableColumn('svy_qst_sc', 'other_answer_label');
	}
?>
<#3113>
<?php
	$ilDB->addIndex('svy_category',array('other'),'i2');
?>
<#3114>
<?php
	$ilDB->dropIndex('svy_category', 'i2');
?>
<#3115>
<?php
	if($ilDB->tableColumnExists('svy_category','scale'))
	{
		$ilDB->dropTableColumn('svy_category', 'scale');
	}
?>
<#3116>
<?php
	if($ilDB->tableColumnExists('svy_category','other'))
	{
		$ilDB->dropTableColumn('svy_category', 'other');
	}
?>
<#3117>
<?php
	if(!$ilDB->tableColumnExists('svy_variable','other'))
	{
	  $ilDB->addTableColumn("svy_variable", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
	}
?>
<#3118>
<?php
	if($ilDB->tableColumnExists('svy_qst_mc','use_other_answer'))
	{
		$ilDB->dropTableColumn('svy_qst_mc', 'use_other_answer');
	}
?>
<#3119>
<?php
	if($ilDB->tableColumnExists('svy_qst_sc','use_other_answer'))
	{
		$ilDB->dropTableColumn('svy_qst_sc', 'use_other_answer');
	}
?>
<#3120>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_mc','use_min_answers'))
	{
		$ilDB->addTableColumn("svy_qst_mc", "use_min_answers", array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
		$ilDB->addTableColumn("svy_qst_mc", "nr_min_answers", array("type" => "integer", "length" => 2, "notnull" => false));
	}
?>
<#3121>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_matrixrows','other'))
	{
		$ilDB->addTableColumn("svy_qst_matrixrows", "other", array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
	}
?>
<#3122>
<?php
	if(!$ilDB->tableColumnExists('svy_question','label'))
	{
		$ilDB->addTableColumn("svy_question", "label", array("type" => "text", "length" => 255, "notnull" => false));
	}
?>
<#3123>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_matrixrows','label'))
	{
		$ilDB->addTableColumn("svy_qst_matrixrows", "label", array("type" => "text", "length" => 255, "notnull" => false));
	}
?>
<#3124>
<?php
	if(!$ilDB->tableColumnExists('svy_variable','scale'))
	{
		$ilDB->addTableColumn("svy_variable", "scale", array("type" => "integer", "length" => 3, "notnull" => false));
	}
?>
<#3125>
<?php
	if(!$ilDB->tableColumnExists('svy_svy','mailnotification'))
	{
		$ilDB->addTableColumn("svy_svy", "mailnotification", array("type" => "integer", "length" => 1, "notnull" => false));
	}
	if(!$ilDB->tableColumnExists('svy_svy','mailaddresses'))
	{
		$ilDB->addTableColumn("svy_svy", "mailaddresses", array("type" => "text", "length" => 2000, "notnull" => false));
	}
	if(!$ilDB->tableColumnExists('svy_svy','mailparticipantdata'))
	{
		$ilDB->addTableColumn("svy_svy", "mailparticipantdata", array("type" => "text", "length" => 4000, "notnull" => false));
	}
?>
<#3126>
<?php
	if(!$ilDB->tableColumnExists('svy_anonymous','externaldata'))
	{
		$ilDB->addTableColumn("svy_anonymous", "externaldata", array("type" => "text", "length" => 4000, "notnull" => false));
	}
?>
<#3127>
<?php
	if(!$ilDB->tableColumnExists('svy_constraint','conjunction'))
	{
		$ilDB->addTableColumn("svy_constraint", "conjunction", array("type" => "integer", "length" => 2, "default" => 0, "notnull" => true));
	}
?>
<#3128>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3129>
<?php
if(!$ilDB->tableExists('booking_object'))
{
	$ilDB->createTable('booking_object',array(
		'booking_object_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 255,
			'notnull' => true
		),
		'type_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_object', array('booking_object_id'));
	$ilDB->createSequence('booking_object');
}
?>

<#3130>
<?php
	@rename(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilFiles',CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilFile');
?>
<#3131>
<?php
	@rename(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilEvents',CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilSession');
?>

<#3132>
<?php
function dirRek($dir)
{
	$dp = @opendir($dir);
	while($file = @readdir($dp))
	{
		if($file == '.' or $file == '..')
		{
			continue;
		}
		if(substr($file,0,7) == 'course_')
		{
			$parts = explode('_',$file);
			@rename($dir.$file,$dir.'crs_'.$parts[1]);
			continue;
		}
		if(is_dir($dir.$file))
		{
			dirRek($dir.$file.DIRECTORY_SEPARATOR);
		}
	}
	@closedir($dp);
}

$dir = CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilCourses'.DIRECTORY_SEPARATOR;
dirRek($dir);
?>
<#3133>
<?php
	@rename(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilCourses',CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'ilCourse');
?>

<#3134>
<?php
	$set = $ilDB->query("SELECT obj_id FROM bookmark_data WHERE ".
		" obj_id = ".$ilDB->quote(1, "integer"));
	$rec = $ilDB->fetchAssoc($set);
	if ($rec["obj_id"] != 1)
	{
		$ilDB->manipulate("INSERT INTO bookmark_data ".
			"(obj_id, user_id, title, description, target, type) VALUES (".
			$ilDB->quote(1, "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote("dummy_folder", "text").",".
			$ilDB->quote("", "text").",".
			$ilDB->quote("", "text").",".
			$ilDB->quote("bmf", "text").
			")");
	}
?>

<#3135>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3136>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 101)
	{
		$set = $ilDB->query("SELECT * FROM object_data WHERE type = 'sty'");
		while ($rec = $ilDB->fetchAssoc($set))	// all styles
		{
			$ast = array(
				array("tag" => "a", "type" => "link", "class" => "FileLink",
					"par" => array(
						array("name" => "text-decoration", "value" => "undeline"),
						array("name" => "font-weight", "value" => "normal"),
						array("name" => "color", "value" => "blue")
						)),
				array("tag" => "div", "type" => "glo_overlay", "class" => "GlossaryOverlay",
					"par" => array(
						array("name" => "background-color", "value" => "#FFFFFF"),
						array("name" => "border-color", "value" => "#A0A0A0"),
						array("name" => "border-style", "value" => "solid"),
						array("name" => "border-width", "value" => "2px"),
						array("name" => "padding-top", "value" => "5px"),
						array("name" => "padding-bottom", "value" => "5px"),
						array("name" => "padding-left", "value" => "5px"),
						array("name" => "padding-right", "value" => "5px")
						))
				);

			foreach($ast as $st)
			{
				$set2 = $ilDB->query("SELECT * FROM style_char WHERE ".
					"style_id = ".$ilDB->quote($rec["obj_id"], "integer")." AND ".
					"characteristic = ".$ilDB->quote($st["class"], "text")." AND ".
					"type = ".$ilDB->quote($st["type"], "text"));
				if (!$ilDB->fetchAssoc($set2))
				{
					$q = "INSERT INTO style_char (style_id, type, characteristic, hide)".
						" VALUES (".
						$ilDB->quote($rec["obj_id"], "integer").",".
						$ilDB->quote($st["type"], "text").",".
						$ilDB->quote($st["class"], "text").",".
						$ilDB->quote(0, "integer").")";
//echo "<br>-$q-";
					$ilDB->manipulate($q);
					foreach ($st["par"] as $par)
					{
						$spid = $ilDB->nextId("style_parameter");
						$q = "INSERT INTO style_parameter (id, style_id, type, class, tag, parameter, value)".
							" VALUES (".
							$ilDB->quote($spid, "integer").",".
							$ilDB->quote($rec["obj_id"], "integer").",".
							$ilDB->quote($st["type"], "text").",".
							$ilDB->quote($st["class"], "text").",".
							$ilDB->quote($st["tag"], "text").",".
							$ilDB->quote($par["name"], "text").",".
							$ilDB->quote($par["value"], "text").
							")";
//echo "<br>-$q-";
					$ilDB->manipulate($q);
					}
				}
			}
		}
	}

?>

<#3137>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 102)
	{
		include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
		ilDBUpdate3136::copyStyleClass("IntLink", "GlossaryLink", "link", "a");
	}

?>

<#3138>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 103)
	{
		include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
		ilDBUpdate3136::addStyleClass("GlossaryOvTitle", "glo_ovtitle", "h1",
					array("font-size" => "120%",
						  "margin-bottom" => "10px",
						  "margin-top" => "10px",
						  "font-weight" => "normal"
						  ));
	}
?>

<#3139>
<?php
	$setting = new ilSetting();
	$se_db = (int) $setting->get("se_db");

	if($se_db <= 104)
	{
		include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
		ilDBUpdate3136::addStyleClass("GlossaryOvCloseLink", "glo_ovclink", "a",
					array("text-decoration" => "underline",
						  "font-weight" => "normal",
						  "color" => "blue"
						  ));
		ilDBUpdate3136::addStyleClass("GlossaryOvUnitGloLink", "glo_ovuglink", "a",
					array("text-decoration" => "underline",
						  "font-weight" => "normal",
						  "color" => "blue"
						  ));
		ilDBUpdate3136::addStyleClass("GlossaryOvUGListLink", "glo_ovuglistlink", "a",
					array("text-decoration" => "underline",
						  "font-weight" => "normal",
						  "color" => "blue"
						  ));

	}

?>
<#3140>
<?php
if(!$ilDB->tableExists('booking_schedule'))
{
	$ilDB->createTable('booking_schedule',array(
		'booking_schedule_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 255,
			'notnull' => true
		),
		'pool_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'deadline'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'rent_min'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'rent_max'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'raster'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'auto_break'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'definition'	=> array(
			'type'	=> 'text',
			'length' => 500,
			'notnull' => true,
			'fixed' => false
		)
	));
	$ilDB->addPrimaryKey('booking_schedule', array('booking_schedule_id'));
	$ilDB->createSequence('booking_schedule');
}
?>
<#3141>
<?php

	$ilDB->addTableColumn("usr_data", "sel_country", array(
		"type" => "text",
		"notnull" => false,
		"default" => "",
		"length" => 2
		));

?>
<#3142>
<?php

	$ilDB->addTableColumn("booking_type", "schedule_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
		));

?>
<#3143>
<?php

	$ilDB->addTableColumn("booking_object", "schedule_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
		));

?>


<#3144>
<?php

$id = $ilDB->nextId('object_data');

// register new object type 'book' for booking manager
$query = "INSERT INTO object_data (obj_id,type, title, description, owner, create_date, last_update) ".
		"VALUES (".$ilDB->quote($id, 'integer').",".$ilDB->quote('typ', 'text').
		", ".$ilDB->quote('book', 'text').", ".$ilDB->quote('Booking Manager', 'text').
		", ".$ilDB->quote(-1, 'integer').", ".$ilDB->now().", ".$ilDB->now().")";
$this->db->query($query);

$query = "SELECT obj_id FROM object_data WHERE type = ".$ilDB->quote('typ', 'text').
	" AND title = ".$ilDB->quote('book', 'text');
$res = $this->db->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

// add rbac operations for booking object
// 1: edit_permissions, 2: visible, 3: read, 4:write
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(1, 'integer').")";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(2, 'integer').")";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(3, 'integer').")";
$this->db->query($query);
$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(4, 'integer').")";
$this->db->query($query);
?>

<#3145>
<?php
	$setting = new ilSetting();
	$setting->set("usr_settings_hide_sel_country", 1);
	$setting->set("usr_settings_disable_sel_country", 1);
	$setting->set("usr_settings_visib_reg_sel_country" ,0);
	$setting->set("usr_settings_visib_lua_sel_country" ,0);
	$setting->set("usr_settings_changeable_lua_sel_country" ,0);
?>

<#3146>
<?php
if(!$ilDB->tableExists('cal_registrations'))
{
	$ilDB->createTable('cal_registrations',array(
		'cal_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'usr_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('cal_registrations', array('cal_id', 'usr_id'));
}
?>

<#3147>
<?php
if(!$ilDB->tableExists('booking_reservation'))
{
	$ilDB->createTable('booking_reservation',array(
		'booking_reservation_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'object_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'date_from'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'date_to'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'status'	=> array(
			'type'	=> 'integer',
			'length'=> 2,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('booking_reservation', array('booking_reservation_id'));
	$ilDB->createSequence('booking_reservation');
}
?>
<#3148>
<?php
	$ilDB->addTableColumn("cal_registrations", "dstart", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 4
		));
	
	$ilDB->addTableColumn("cal_registrations", "dend", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 4
		));
		
	$ilDB->dropPrimaryKey('cal_registrations');
	$ilDB->addPrimaryKey('cal_registrations', array('cal_id', 'usr_id','dstart','dend'));
?>
<#3149>
<?php
	$ilDB->addTableColumn("svy_anonymous", "sent", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 2
		));
?>
<#3150>
<?php
	$ilDB->addIndex('svy_anonymous',array('sent'),'i3');
?>

<#3151>
<?php
if(!$ilDB->tableExists('booking_settings'))
{
	$ilDB->createTable('booking_settings',array(
		'booking_pool_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'public_log'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		),
		'pool_offline'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('booking_settings', array('booking_pool_id'));
}
?>
<#3152>
<?php
	$ilDB->addTableColumn("qpl_qst_essay", "matchcondition", array(
		"type" => "integer",
		"notnull" => true,
		"default" => 0,
		"length" => 2
		));
?>

<#3153>
<?php
	$ilDB->addTableColumn("booking_settings", "slots_no", array(
		"type" => "integer",
		"notnull" => false,
		"default" => 0,
		"length" => 2
		));
?>
<#3154>
<?php
	
	$permission_ordering = array(
		'visible'		=> 1000,
		'join'			=> 1200,
		'leave'			=> 1400,
		'read'			=> 2000,
		'edit_content'	=> 3000,
		'add_thread'	=> 3100,
		'edit_event'	=> 3600,
		'moderate'		=> 3700,
		'moderate_frm'	=> 3750,
		'edit_learning_progress' => 3600,
		'copy'			=> 4000,
		'write'			=> 6000,
		'read_users'	=> 7000,
		'cat_administrate_users' => 7050,
		'invite'			=> 7200,
		'tst_statistics'	=> 7100, 
		'delete'		=> 8000,
		'edit_permission' => 9000
	);
	
	foreach($permission_ordering as $op => $order)
	{
		$query = "UPDATE rbac_operations SET ".
			'op_order = '.$ilDB->quote($order,'integer').' '.
			'WHERE operation = '.$ilDB->quote($op,'text').' ';
		$ilDB->manipulate($query);
	}
?>

<#3155>
<?php
	if($ilDB->tableColumnExists('svy_svy','mailaddresses'))
	{
		$ilDB->dropTableColumn('svy_svy', 'mailaddresses');
	}
	if($ilDB->tableColumnExists('svy_svy','mailparticipantdata'))
	{
		$ilDB->dropTableColumn('svy_svy', 'mailparticipantdata');
	}
?>
<#3156>
<?php
	if(!$ilDB->tableColumnExists('svy_svy','mailaddresses'))
	{
		$ilDB->addTableColumn("svy_svy", "mailaddresses", array("type" => "text", "length" => 2000, "notnull" => false));
		$ilDB->addTableColumn("svy_svy", "mailparticipantdata", array("type" => "text", "length" => 4000, "notnull" => false));
	}
?>

<#3157>
<?php
	if(!$ilDB->tableColumnExists('svy_qst_mc','nr_max_answers'))
	{
		$ilDB->addTableColumn("svy_qst_mc", "nr_max_answers", array("type" => "integer", "length" => 2, "notnull" => false));
	}
?>
<#3158>
<?php
if(!$ilDB->tableExists('svy_times'))
{
	$ilDB->createTable('svy_times',array(
		'finished_fi'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'entered_page'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		),
		'left_page'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => false
		)
	));
	$ilDB->addIndex('svy_times',array('finished_fi'),'i1');
}
?>

<#3159>
<?php
	$query = 'UPDATE rbac_operations SET op_order = '.$ilDB->quote(9999,'integer').' WHERE class = '.$ilDB->quote('create','text');
	$ilDB->manipulate($query);
?>

<#3160>
<?php

// add create operation for booking pools

$ops_id = $ilDB->nextId('rbac_operations');

$query = 'INSERT INTO rbac_operations (ops_id, operation, class, description, op_order)'.
	' VALUES ('.$ilDB->quote($ops_id,'integer').','.$ilDB->quote('create_book','text').
	','.$ilDB->quote('create','text').','.$ilDB->quote('create booking pool','text').
	','.$ilDB->quote(9999,'integer').')';
$ilDB->query($query);

// add create booking pool for root,crs,cat,fold and grp
foreach(array('cat', 'crs', 'grp', 'fold', 'root') as $type)
{
	$query = 'SELECT obj_id FROM object_data WHERE type='.$ilDB->quote('typ','text').
		' AND title='.$ilDB->quote($type,'text');
	$res = $ilDB->query($query);
	$row = $ilDB->fetchAssoc($res);
	$typ_id = $row['obj_id'];

	$query = 'INSERT INTO rbac_ta (typ_id, ops_id) VALUES ('.$ilDB->quote($typ_id,'integer').
		','.$ilDB->quote($ops_id,'integer').')';
	$ilDB->query($query);
}

?>
<#3161>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3162>
<?php
	$ilDB->manipulate("UPDATE ut_lp_marks SET ".
		" status_dirty = ".$ilDB->quote(1, "integer")
		);
?>
<#3163>
<?php
	$ilDB->addIndex("conditions", array("target_obj_id", "target_type"), "tot");
?>
<#3164>
<?php
if($ilDB->tableExists('svy_times'))
{
	$ilDB->addTableColumn("svy_times", "first_question", array("type" => "integer", "length" => 4, "notnull" => false));
}
?>
<#3165>
<?php
if(!$ilDB->tableExists('svy_settings'))
{
	$ilDB->createTable('svy_settings',array(
		'settings_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'usr_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'keyword'	=> array(
			'type'	=> 'text',
			'length'=> 40,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 400,
			'notnull' => false
		),
		'value'	=> array(
			'type'	=> 'clob',
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('svy_settings', array('settings_id'));
	$ilDB->addIndex('svy_settings',array('usr_id'),'i1');
}
?>
<#3166>
<?php
	$ilDB->createSequence('svy_settings');
?>
<#3167>
<?php
if(!$ilDB->tableExists('cal_ch_settings'))
{
	$ilDB->createTable('cal_ch_settings',array(
		'user_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'admin_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
	));
	$ilDB->addPrimaryKey('cal_ch_settings', array('user_id', 'admin_id'));
}
?>
<#3168>
<?php
	if(!$ilDB->tableColumnExists('booking_entry','target_obj_id'))
	{
		$ilDB->addTableColumn("booking_entry", "target_obj_id", array("type" => "integer", "length" => 4, "notnull" => false));
	}
?>
<#3169>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3170>
<?php
	
	$permission_ordering = array(
		'add_post'		=> 3050,
		'edit_roleassignment' => 2500,
		'push_desktop_items'			=> 2400,
		'search'			=> 300,
		'export_memberdata'			=> 400,
		'edit_userasignment'	=> 2600,
	);
	
	foreach($permission_ordering as $op => $order)
	{
		$query = "UPDATE rbac_operations SET ".
			'op_order = '.$ilDB->quote($order,'integer').' '.
			'WHERE operation = '.$ilDB->quote($op,'text').' ';
		$ilDB->manipulate($query);
	}
?>
<#3171>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3172>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		// test sequence
		if (!$ilDB->tableColumnExists('svy_category', 'title_tmp'))
		{
			$ilDB->addTableColumn("svy_category", "title_tmp", array(
			"type" => "text",
			"length" => 1000,
			"notnull" => false,
			"default" => null));
		}
	}
?>
<#3173>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		$ilDB->manipulate('UPDATE svy_category SET title_tmp = title');
	}
?>
<#3174>
<?php
	if($ilDB->getDBType() == 'oracle')
	{
		if ($ilDB->tableColumnExists('svy_category', 'title'))
			$ilDB->dropTableColumn('svy_category', 'title');

		$ilDB->renameTableColumn("svy_category", "title_tmp", "title");
	}
?>
<#3175>
<?php
	if($ilDB->getDBType() == 'mysql')
	{
		$ilDB->modifyTableColumn('svy_category','title', array("type" => "text", "length" => 1000, "default" => null, "notnull" => false));
	}
?>
<#3176>
<?php
if (!$ilDB->tableColumnExists('qpl_qst_ordering', 'scoring_type'))
{
	$ilDB->addTableColumn("qpl_qst_ordering", "scoring_type", array(
		"type" => "integer",
		"length" => 3,
		"notnull" => true,
		"default" => 0)
	);
	$ilDB->addTableColumn("qpl_qst_ordering", "reduced_points", array(
		"type" => "float",
		"notnull" => true,
		"default" => 0)
	);
}
?>
<#3177>
<?php
if (!$ilDB->tableColumnExists('tst_tests', 'mailnottype'))
{
	$ilDB->addTableColumn("tst_tests", "mailnottype", array(
		"type" => "integer",
		"length" => 2,
		"notnull" => true,
		"default" => 0)
	);
}
?>
<#3178>
<?php

	$query = "UPDATE crs_settings SET view_mode = 0 WHERE view_mode = 3";
	$ilDB->manipulate($query);
?>
<#3179>
<?php

// copy permission id
$query = "SELECT * FROM rbac_operations WHERE operation = ".$ilDB->quote('copy','text');
$res = $ilDB->query($query);
$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
$ops_id = $row->ops_id;

$all_types = array('spl','qpl');
foreach($all_types as $type)
{
	$query = "SELECT obj_id FROM object_data WHERE type = 'typ' AND title = ".$ilDB->quote($type,'text');
	$res = $ilDB->query($query);
	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);

	$query = "INSERT INTO rbac_ta (typ_id,ops_id) ".
		"VALUES( ".
		$ilDB->quote($row->obj_id,'integer').', '.
		$ilDB->quote($ops_id,'integer').' '.
		')';
	$ilDB->manipulate($query);
}
?>
<#3180>
<?php

// Calendar settings
$query = 'SELECT obj_id FROM object_data WHERE type = '.$ilDB->quote('typ','text').
	' AND title = '.$ilDB->quote('cals','text');
$res = $ilDB->query($query);
$row = $res->fetchRow();
$cals = $row[0];

$insert = false;
$query = 'SELECT ops_id FROM rbac_operations WHERE operation = '.$ilDB->quote('add_consultation_hours','text');
$res = $ilDB->query($query);
if($ilDB->numRows($res))
{
	$row = $res->fetchRow();
	$ops_id = (int)$row[0];

	// remove old (faulty) ops [see #3083]
	if($ops_id === 0)
	{
		$query = 'DELETE FROM rbac_operations WHERE operation = '.$ilDB->quote('add_consultation_hours','text');
		$ilDB->query($query);
		$query = 'DELETE FROM rbac_ta  WHERE ops_id = '.$ilDB->quote(0,'integer').
			' AND typ_id = '.$ilDB->quote($cals,'integer');
		$ilDB->query($query);

		$insert = true;
	}
}
else
{
	$insert = true;
}

if($insert)
{
	// new permission
	$new_ops_id = $ilDB->nextId('rbac_operations');
	$query = 'INSERT INTO rbac_operations (ops_id,operation,description,class,op_order) '.
		'VALUES( '.
		$ilDB->quote($new_ops_id,'integer').', '.
		$ilDB->quote('add_consultation_hours','text').', '.
		$ilDB->quote('Add Consultation Hours Calendar','text').", ".
		$ilDB->quote('object','text').", ".
		$ilDB->quote(300,'integer').
		')';
	$res = $ilDB->query($query);

	$ilDB->manipulateF(
		'INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
		array('integer','integer'),
		array($cals, $new_ops_id));
}

?>
<#3181>
<?php
if (!$ilDB->tableColumnExists('svy_finished', 'lastpage'))
{
	$ilDB->addTableColumn("svy_finished", "lastpage", array(
		"type" => "integer",
		"length" => 4,
		"notnull" => true,
		"default" => 0)
	);
}
?>
<#3182>
<?php
if (!$ilDB->tableColumnExists('svy_phrase_cat', 'other'))
{
  $ilDB->addTableColumn("svy_phrase_cat", "other", array("type" => "integer", "length" => 2, "notnull" => true, "default" => 0));
}
?>
<#3183>
<?php
if (!$ilDB->tableColumnExists('svy_phrase_cat', 'scale'))
{
  $ilDB->addTableColumn("svy_phrase_cat", "scale", array("type" => "integer", "length" => 4, "notnull" => false, "default" => null));
}
?>
<#3184>
<?php
if (!$ilDB->tableColumnExists('tst_tests', 'exportsettings'))
{
  $ilDB->addTableColumn("tst_tests", "exportsettings", array("type" => "integer", "length" => 4, "notnull" => true, "default" => 0));
}
?>
<#3185>
<?php
	$query = 'UPDATE rbac_operations SET operation = '.$ilDB->quote('create_usr','text').' WHERE operation = '.$ilDB->quote('create_user','text');
	$ilDB->manipulate($query);
?>
<#3186>
<?php

// create new table
if(!$ilDB->tableExists('booking_schedule_slot'))
{
	$ilDB->createTable('booking_schedule_slot',array(
		'booking_schedule_id'	=> array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'day_id'	=> array(
			'type'	=> 'text',
			'length'=> 2,
			'notnull' => true
		),
		'slot_id'	=> array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => true
		),
		'times'	=> array(
			'type'	=> 'text',
			'length'=> 50,
			'notnull' => true
		)
	));
	$ilDB->addPrimaryKey('booking_schedule_slot', array('booking_schedule_id', 'day_id', 'slot_id'));
	
	if($ilDB->tableColumnExists('booking_schedule','definition'))
	{
		// migrate existing schedules
		$set = $ilDB->query('SELECT booking_schedule_id,definition FROM booking_schedule');
		while($row = $ilDB->fetchAssoc($set))
		{
			$definition = @unserialize($row["definition"]);
			if($definition)
			{
				foreach($definition as $day_id => $slots)
				{
					foreach($slots as $slot_id => $times)
					{
						$fields = array(
							"booking_schedule_id" => array('integer', $row["booking_schedule_id"]),
							"day_id" => array('text', $day_id),
							"slot_id" => array('integer', $slot_id),
							"times" => array('text', $times)
							);
						$ilDB->insert('booking_schedule_slot', $fields);
					}
				}
			}
		}

		// remove old column
		$ilDB->dropTableColumn('booking_schedule', 'definition');
	}
}
?>
<#3187>
<?php
if (!$ilDB->tableColumnExists('notification', 'page_id'))
{
  $ilDB->addTableColumn("notification", "page_id", array("type" => "integer", "length" => 4, "notnull" => false, "default" => 0));
}
?>
<#3188>
<?php
	$ilDB->addTableColumn("svy_svy", "startdate_tmp", array(
		"type" => "text",
		"notnull" => false,
		'length'=> 14,
		"default" => null
	));
?>
<#3189>
<?php
$res = $ilDB->query('SELECT survey_id, startdate FROM svy_svy');
while ($row = $ilDB->fetchAssoc($res))
{
	if (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $row['startdate'], $matches))
	{
		$ilDB->manipulateF('UPDATE svy_svy SET startdate_tmp = %s WHERE survey_id = %s',
			array('text', 'integer'),
			array(sprintf("%04d%02d%02d%02d%02d%02d", $matches[1], $matches[2], $matches[3], 0, 0, 0), $row['survey_id'])
		);
	}
}
?>
<#3190>
<?php
$ilDB->dropTableColumn('svy_svy', 'startdate');
?>
<#3191>
<?php
$ilDB->renameTableColumn("svy_svy", "startdate_tmp", "startdate");
?>
<#3192>
<?php
	$ilDB->addTableColumn("svy_svy", "enddate_tmp", array(
		"type" => "text",
		"notnull" => false,
		'length'=> 14,
		"default" => null
	));
?>
<#3193>
<?php
$res = $ilDB->query('SELECT survey_id, enddate FROM svy_svy');
while ($row = $ilDB->fetchAssoc($res))
{
	if (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $row['enddate'], $matches))
	{
		$ilDB->manipulateF('UPDATE svy_svy SET enddate_tmp = %s WHERE survey_id = %s',
			array('text', 'integer'),
			array(sprintf("%04d%02d%02d%02d%02d%02d", $matches[1], $matches[2], $matches[3], 0, 0, 0), $row['survey_id'])
		);
	}
}
?>
<#3194>
<?php
$ilDB->dropTableColumn('svy_svy', 'enddate');
?>
<#3195>
<?php
$ilDB->renameTableColumn("svy_svy", "enddate_tmp", "enddate");
?>

<#3196>
<?php
	$ilDB->addTableColumn("export_file_info", "filename", array(
		"type" => "text",
		"notnull" => false,
		'length'=> 64,
		"default" => null
	));
?>
<#3197>
<?php
	$query = "UPDATE export_file_info SET filename = file_name ";
	$ilDB->manipulate($query);
?>

<#3198>
<?php
	$ilDB->dropPrimaryKey('export_file_info');
?>
<#3199>
<?php
	$ilDB->addPrimaryKey('export_file_info',array('obj_id','export_type','filename'));
?>

<#3200>
<?php
	
	// Invalid assign flags
	$query = "SELECT rol_id FROM rbac_fa ".
		"WHERE assign = ".$ilDB->quote('y','text').' '.
		"GROUP BY rol_id HAVING count(*) > 1";
	$res = $ilDB->query($query);
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$role_id = $row->rol_id;
		
		$query = "SELECT depth, fa.parent parent FROM rbac_fa fa ".
			"JOIN tree t ON fa.parent = child ".
			"WHERE rol_id = ".$ilDB->quote($role_id,'integer').' '.
			"AND assign = ".$ilDB->quote('y','text').' '.
			"ORDER BY depth, fa.parent";
		$assignable_res = $ilDB->query($query);
		$first = true;
		while($assignable_row = $assignable_res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($first)
			{
				$first = false;
				continue;
			}
			// Only for security
			if($assignable_row->parent == ROLE_FOLDER_ID)
			{
				continue;
			}
			$GLOBALS['ilLog']->write(__METHOD__.': Ressetting assignable flag for role_id: '.$role_id.' parent: '.$assignable_row->parent);
			$query = "UPDATE rbac_fa SET assign = ".$ilDB->quote('n','text').' '.
				"WHERE rol_id = ".$ilDB->quote($role_id,'integer').' '.
				"AND parent = ".$ilDB->quote($assignable_row->parent,'integer');
			$ilDB->manipulate($query);
		} 
	}
?>
<#3201>
<?php
if (!$ilDB->tableColumnExists('tst_test_random', 'sequence'))
{
	$ilDB->addTableColumn("tst_test_random", "sequence", array(
	"type" => "integer",
	"length" => 4,
	"notnull" => true,
	"default" => 0));
}
?>
<#3202>
<?php
if ($ilDB->tableColumnExists('tst_test_random', 'sequence'))
{
	$ilDB->manipulate("UPDATE tst_test_random SET sequence = test_random_id");
}
?>
<#3203>
<?php
if (!$ilDB->tableColumnExists('usr_session', 'remote_addr'))
{
	$ilDB->addTableColumn("usr_session", "remote_addr", array(
	"type" => "text",
	"length" => 50,
	"notnull" => false,
	"default" => null));
}
?>
<#3204>
<?php

include_once "Services/Tracking/classes/class.ilLPMarks.php";

$set = $ilDB->query("SELECT event_id,usr_id,mark,e_comment".
	" FROM event_participants".
	" WHERE mark IS NOT NULL OR e_comment IS NOT NULL");
while($row = $ilDB->fetchAssoc($set))
{
	// move to ut_lp_marks

	$fields = array();
	$fields["mark"] = array("text", $row["mark"]);
	$fields["u_comment"] = array("text", $row["e_comment"]);
	// $fields["status_changed"] = array("timestamp", date("Y-m-d H:i:s"));

	$where = array();
	$where["obj_id"] = array("integer", $row["event_id"]);
	$where["usr_id"] = array("integer", $row["usr_id"]);

	$old = $ilDB->query("SELECT obj_id,usr_id".
		" FROM ut_lp_marks".
		" WHERE obj_id = ".$ilDB->quote($row["event_id"]).
		" AND usr_id = ".$ilDB->quote($row["usr_id"]));
	if($ilDB->numRows($old))
	{
		$ilDB->update("ut_lp_marks", $fields, $where);
	}
	else
	{
		$fields = array_merge($fields, $where);
		$ilDB->insert("ut_lp_marks", $fields);
	}

	
	// delete old values
	
	$fields = array();
	$fields["mark"] = array("text", null);
	$fields["e_comment"] = array("text", null);
	
	$where = array();
	$where["event_id"] = array("integer", $row["event_id"]);
	$where["usr_id"] = array("integer", $row["usr_id"]);

	$ilDB->update("event_participants", $fields, $where);
}
?>
<#3205>
<?php
	if(!$ilDB->tableColumnExists('export_options','pos'))
	{
		$ilDB->addTableColumn(
			'export_options',
			'pos',
			array(
				'type' 		=> 'integer', 
				'length' 	=> 4,
				'notnull'	=> true,
				'default'	=> 0
			)
		);
	}
?>

<#3206>
<?php
	$ilDB->modifyTableColumn('ldap_server_settings', 'filter',
		array("type" => "text", "length" => 512, "notnull" => false));
?>
<#3207>
<?php
$ilDB->manipulate
(
	'UPDATE mail_obj_data SET title = '.$ilDB->quote('z_local', 'text').' '
   .'WHERE title != '.$ilDB->quote('z_local', 'text').' AND m_type = '.$ilDB->quote('local', 'text')
); 
?>
<#3208>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTEMenu", "rte_menu", "div",
				array());
?>
<#3209>
<?php
$set = $ilDB->query("SELECT obj_id FROM object_data WHERE type = ".$ilDB->quote("tst", "text"));
while ($r = $ilDB->fetchAssoc($set))
{
	$ilDB->manipulate("UPDATE ut_lp_marks SET ".
		" status_dirty = ".$ilDB->quote(1, "integer").
		" WHERE obj_id = ".$ilDB->quote($r["obj_id"], "integer")
		);
}
?>
<#3210>
<?php
$ilDB->addTableColumn("sahs_lm", "entry_page", array(
	"type" => "integer",
	"notnull" => true,
	"default" => 0,
	"length" => 4
));
?>
<#3211>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3212>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3213>
<?php
// convert old qpl export files
$qpl_export_base = ilUtil::getDataDir()."/qpl_data/";
// quit if import dir not available
if (@is_dir($qpl_export_base) && is_writeable($qpl_export_base))
{
	// open directory
	$h_dir = dir($qpl_export_base);

	// get files and save the in the array
	while ($entry = $h_dir->read())
	{
		if ($entry != "." && $entry != "..")
		{
			if (@is_dir($qpl_export_base . $entry))
			{
				$q_dir = dir($qpl_export_base . $entry);
				while ($q_entry = $q_dir->read())
				{
					if ($q_entry != "." and $q_entry != "..")
					{
						if (@is_dir($qpl_export_base . $entry . '/' . $q_entry) && strcmp($q_entry, 'export') == 0)
						{
							$exp_dir = dir($qpl_export_base . $entry . '/' . $q_entry);
							while ($exp_entry = $exp_dir->read())
							{
								if (@is_file($qpl_export_base . $entry . '/' . $q_entry . '/' . $exp_entry))
								{
									$res = preg_match("/^([0-9]{10}_{2}[0-9]+_{2})(qpl__)*([0-9]+)\.(zip)\$/", $exp_entry, $matches);
									if ($res)
									{
										switch ($matches[4])
										{
											case 'zip':
												if (!@is_dir($qpl_export_base . $entry . '/' .  'export_zip')) ilUtil::makeDir($qpl_export_base . $entry . '/' . 'export_zip');
												@rename($qpl_export_base . $entry . '/' . $q_entry . '/' . $exp_entry, $qpl_export_base . $entry . '/' . 'export_zip' . '/' . $matches[1].'qpl_'.$matches[3].'.zip');
												break;
										}
									}
								}
							}
							$exp_dir->close();
							if (@is_dir($qpl_export_base . $entry . '/' . $q_entry)) ilUtil::delDir($qpl_export_base . $entry . '/' . $q_entry);
						}
					}
				}
				$q_dir->close();
			}
		}
	}
	$h_dir->close();
}
?>
<#3214>
<?php
if($ilDB->tableColumnExists('svy_svy','mailaddresses'))
{
	$ilDB->addTableColumn("svy_svy", "mailaddresses_tmp", array("type" => "text", "length" => 2000, "notnull" => false, "default" => null));
	$ilDB->manipulate('UPDATE svy_svy SET mailaddresses_tmp = mailaddresses');
	$ilDB->dropTableColumn('svy_svy', 'mailaddresses');
	$ilDB->renameTableColumn("svy_svy", "mailaddresses_tmp", "mailaddresses");
}
?>
<#3215>
<?php
if($ilDB->tableColumnExists('svy_svy','mailparticipantdata'))
{
	$ilDB->addTableColumn("svy_svy", "mailparticipantdata_tmp", array("type" => "text", "length" => 4000, "notnull" => false, "default" => null));
	$ilDB->manipulate('UPDATE svy_svy SET mailparticipantdata_tmp = mailparticipantdata');
	$ilDB->dropTableColumn('svy_svy', 'mailparticipantdata');
	$ilDB->renameTableColumn("svy_svy", "mailparticipantdata_tmp", "mailparticipantdata");
}
?>
<#3216>
<?php
$ilDB->addTableColumn("page_layout", "style_id", array(
	"type" => "integer",
	"notnull" => false,
	"default" => 0,
	"length" => 4
));
?>
<#3217>
<?php
$ilDB->addIndex('frm_thread_access', array('access_last'), 'i1');
?>
<#3218>
<?php
	$setting = new ilSetting();

	$old_setting = $setting->get('disable_anonymous_fora');
	$new_setting = $setting->set('enable_anonymous_fora', $old_setting ? false : true);

	$ilDB->manipulateF('DELETE FROM settings WHERE keyword = %s',
			array('text'), array('disable_anonymous_fora'));

?>
<#3219>
<?php
$fields = array(
	'id' => array
	(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'hide_obj_page' => array
	(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);
$ilDB->createTable('sahs_sc13_sco', $fields);
$ilDB->addPrimaryKey("sahs_sc13_sco", array("id"));
	
?>
<#3220>
<?php
$set = $ilDB->query("SELECT * FROM sahs_sc13_tree_node WHERE ".
	" type = ".$ilDB->quote("sco", "text")
	);
while ($rec = $ilDB->fetchAssoc($set))
{
	$ilDB->manipulate("INSERT INTO sahs_sc13_sco ".
		"(id, hide_obj_page) VALUES (".
		$ilDB->quote($rec["obj_id"], "integer").",".
		$ilDB->quote(0, "integer").
		")");
}
?>

<#3221>
<?php
if(!$ilDB->tableExists('tree_workspace'))
{
	$fields = array (
		'tree'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'child'   => array ('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'parent'    => array ('type' => 'integer', 'length'  => 4,"notnull" => true,"default" => 0),
		'lft'  => array ('type' => 'integer', 'length'  => 4,"notnull" => true,"default" => 0),
		'rgt'  => array ('type' => 'integer', 'length'  => 4,"notnull" => true,"default" => 0),
		'depth'  => array ('type' => 'integer', 'length'  => 2,"notnull" => true,"default" => 0)
	  );
  $ilDB->createTable('tree_workspace', $fields);
  $ilDB->addIndex('tree_workspace', array('child'), 'i1');
  $ilDB->addIndex('tree_workspace', array('parent'), 'i2');
  $ilDB->addIndex('tree_workspace', array('tree'), 'i3');
}
?>
<#3222>
<?php
if(!$ilDB->tableExists('object_reference_ws'))
{
	$fields = array (
		'wsp_id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'obj_id'   => array ('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'deleted'    => array ('type' => 'timestamp', 'notnull' => false)
	  );
  $ilDB->createTable('object_reference_ws', $fields);
  $ilDB->addPrimaryKey('object_reference_ws', array('wsp_id'));
  $ilDB->addIndex('object_reference_ws', array('obj_id'), 'i1');
  $ilDB->addIndex('object_reference_ws', array('deleted'), 'i2');
  $ilDB->createSequence('object_reference_ws');
}
?>
<#3223>
<?php
if(!$ilDB->tableColumnExists('il_object_def','repository'))
{
	$ilDB->addTableColumn("il_object_def", "repository",
		array("type" => "integer", "length" => 1, "notnull" => true, "default" => 1));
}
?>
<#3224>
<?php
if(!$ilDB->tableColumnExists('il_object_def','workspace'))
{
	$ilDB->addTableColumn("il_object_def", "workspace",
		array("type" => "integer", "length" => 1, "notnull" => true, "default" => 0));
}
?>
<#3225>
<?php

		if(!$ilDB->tableColumnExists('ldap_server_settings','authentication'))
		{
			$ilDB->addTableColumn(
				'ldap_server_settings',
				'authentication',
				array(
					'type' => 'integer',
					'length' => '1',
					'notnull' => true,
					'default' => 1
				)
			);
		}
?>

<#3226>
<?php
		if(!$ilDB->tableColumnExists('ldap_server_settings','authentication_type'))
		{
			$ilDB->addTableColumn(
				'ldap_server_settings',
				'authentication_type',
				array(
					'type' => 'integer',
					'length' => '1',
					'notnull' => true,
					'default' => 0
				)
			);
		}
?>
<#3227>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELogo", "rte_menu", "div",
				array("float" => "left"));
?>
<#3228>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELinkBar", "rte_menu", "div",
				array());
?>
<#3229>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELink", "rte_mlink", "a",
				array());
?>
<#3230>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTELinkDisabled", "rte_mlink", "a",
				array());
?>
<#3231>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETree", "rte_tree", "div",
				array());
?>
<#3232>
<?php
$ilDB->addTableColumn("sahs_lm", "final_sco_page", array(
	"type" => "integer",
	"notnull" => true,
	"default" => 0,
	"length" => 4
));
$ilDB->addTableColumn("sahs_lm", "final_lm_page", array(
	"type" => "integer",
	"notnull" => true,
	"default" => 0,
	"length" => 4
));
?>
<#3233>
<?php
if(!$ilDB->tableExists('il_blog_posting'))
{
	$fields = array (
		'id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'blog_id' => array ('type' => 'integer', 'notnull' => true, 'length' => 4, 'default' => 0),
		'title' => array ('type' => 'text', 'notnull' => false, 'length' => 400),
		'created' => array ('type' => 'timestamp', 'notnull' => true)
	  );
  $ilDB->createTable('il_blog_posting', $fields);
  $ilDB->addPrimaryKey('il_blog_posting', array('id'));
  $ilDB->addIndex('il_blog_posting', array('created'), 'i1');
  $ilDB->createSequence('il_blog_posting');
}
?>
<#3234>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTECourse", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEChapter", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTESco", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEAsset", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTECourseDisabled", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEChapterDisabled", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEScoDisabled", "rte_node", "td",
				array());
	ilDBUpdate3136::addStyleClass("RTEAssetDisabled", "rte_node", "td",
				array());
?>
<#3235>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTEAsset", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTECompleted", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTENotAttempted", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTERunning", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEIncomplete", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEPassed", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEFailed", "rte_status", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTEBrowsed", "rte_status", "a",
				array());
?>
<#3236>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETreeLink", "rte_tlink", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTETreeLinkDisabled", "rte_tlink", "a",
				array());
?>

<#3237>
<?php
// this is a fix for patched scorm editor installations (separate branch)
if($ilDB->getDBType() == 'mysql')
{
	$set = $ilDB->query("SELECT max(id) mid FROM style_template");
	$rec = $ilDB->fetchAssoc($set);
	if ($rec["mid"] > 0)
	{
		$ilDB->manipulate("UPDATE style_template_seq SET ".
			" sequence = ".$ilDB->quote($rec["mid"], "integer"));
	}
}
?>
<#3238>
<?php
$ilDB->addTableColumn("page_layout", "special_page", array(
	"type" => "integer",
	"notnull" => false,
	"default" => 0,
	"length" => 1
));
?>
<#3239>
<?php
$ilDB->addTableColumn("il_wiki_data", "public_notes", array(
	"type" => "integer",
	"notnull" => false,
	"default" => 1,
	"length" => 1
));
?>
<#3240>
<?php
if(!$ilDB->tableExists('il_blog'))
{
	$fields = array (
		'id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'notes' => array ('type' => 'integer', 'notnull' => false, 'length' => 1, 'default' => 1)
	  );
  $ilDB->createTable('il_blog', $fields);
  $ilDB->addPrimaryKey('il_blog', array('id'));
}
?>
<#3241>
<?php
if(!$ilDB->tableExists('acl_ws'))
{
	$fields = array (
		'node_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'object_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
	  );
  $ilDB->createTable('acl_ws', $fields);
  $ilDB->addPrimaryKey('acl_ws', array('node_id', 'object_id'));
}
?>
<#3242>
<?php
	if(!$ilDB->tableColumnExists('conditions', 'obligatory'))
	{
		$ilDB->addTableColumn(
			'conditions',
			'obligatory',
			array(
				'type' => 'integer',
				'notnull' => true,
				'length' => 1,
				'default' => 1
			)
		);
	}
?>
<#3243>
<?php
	if(!$ilDB->tableColumnExists('ut_lp_collections', 'grouping_id'))
	{
		$ilDB->addTableColumn(
			'ut_lp_collections',
			'grouping_id',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 4,
				'default'	=> 0
			)
		);
		$ilDB->addTableColumn(
			'ut_lp_collections',
			'num_obligatory',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 4,
				'default'	=> 0
			)
		);
	}
?>
<#3244>
<?php
	if(!$ilDB->tableColumnExists('ut_lp_collections', 'active'))
	{
		$ilDB->addTableColumn(
			'ut_lp_collections',
			'active',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 1,
				'default'	=> 1
			)
		);

	}
?>
<#3245>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("FinalMessage", "sco_fmess", "div",
		array("margin" => "100px", "padding" => "50px", "font-size" => "125%",
			"border-width" => "1px", "border-style" => "solid", "border-color" => "#F0F0F0",
			"background-color" => "#FAFAFA", "text-align" => "center"));
?>
<#3246>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3247>
<?php
	$ilDB->dropPrimaryKey("sahs_sc13_seq_item");
	$ilDB->addPrimaryKey("sahs_sc13_seq_item",
		array("sahs_sc13_tree_node_id", "rootlevel"));
?>
<#3248>
<?php
	$ilDB->addTableColumn("sahs_sc13_seq_item", "importseqxml", array(
		"type" => "clob"));
?>
<#3249>
<?php
	$ilDB->addTableColumn("sahs_lm", "seq_exp_mode", array(
		"type" => "integer", "length" => 1, "notnull" => false, "default" => 0));
?>
<#3250>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3251>
<?php
	if(!$ilDB->tableColumnExists('conditions','num_obligatory'))
	{
		$ilDB->addTableColumn(
			'conditions',
			'num_obligatory',
			array(
				'type'		=> 'integer',
				'notnull'	=> true,
				'length'	=> 1,
				'default'	=> 0
			)
		);
	}
?>
<#3252>
<?php
	if(!$ilDB->tableColumnExists('payment_prices','extension'))
	{
		$ilDB->addTableColumn(
			'payment_prices',
			'extension',
			array(
				'type' => 'integer',
				'length' => '1',
				'notnull' => true,
				'default' => 0
			)
		);
	}
?>
<#3253>
<?php
	if(!$ilDB->tableColumnExists('payment_statistic','access_enddate'))
	{
		$ilDB->addTableColumn(
			'payment_statistic',
			'access_enddate',
			array(
				'type'     => 'timestamp',
				'notnull'	=> false
			)
		);
	}
?>
<#3254>
<?php
	$res = $ilDB->queryf('
		SELECT booking_id, order_date, duration
		FROM payment_statistic
		WHERE duration > %s',
		array('integer'),array(0));

	while($row = $ilDB->fetchAssoc($res))
	{
		$order_date = $row['order_date'];
		$duration = $row['duration'];

		$orderDateYear = date("Y", $row['order_date']);
		$orderDateMonth = date("m", $row['order_date']);
		$orderDateDay = date("d", $row['order_date']);
		$orderDateHour = date("H",$row['order_date']);
		$orderDateMinute = date("i", $row['order_date']);
		$orderDateSecond = date("s", $row['order_date']);

		$access_enddate = date("Y-m-d H:i:s", mktime($orderDateHour, $orderDateMinute, $orderDateSecond,
				$orderDateMonth + $duration, $orderDateDay, $orderDateYear));

		$ilDB->update('payment_statistic',
			array('access_enddate' => array('timestamp', $access_enddate)),
			array('booking_id' => array('integer', $row['booking_id'])));
	}
?>
<#3255>
<?php

if (!$ilDB->tableColumnExists("il_wiki_data", "imp_pages"))
{
	$ilDB->addTableColumn("il_wiki_data", "imp_pages", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3256>
<?php
if (!$ilDB->tableExists('il_wiki_imp_pages'))
{
	$fields = array(
		'wiki_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'ord' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'indent' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
			),
		'page_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			)
	);
	$ilDB->createTable('il_wiki_imp_pages', $fields);
}
?>
<#3257>
<?php
if (!$ilDB->tableExists('adm_settings_template'))
{
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'type' => array(
			'type' => 'text',
			'length' => 5,
			'notnull' => true
			),
		'title' => array(
			'type' => 'text',
			'length' => 100,
			'notnull' => true
			),
		'description' => array(
			'type' => 'clob'
			)
	);
	$ilDB->createTable('adm_settings_template', $fields);
	$ilDB->createSequence('adm_settings_template');
}
?>
<#3258>
<?php
if (!$ilDB->tableExists('adm_set_templ_value'))
{
	$fields = array(
		'template_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'setting' => array(
			'type' => 'text',
			'length' => 40,
			'notnull' => true
			),
		'value' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
			),
		'hide' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
	);
	$ilDB->createTable('adm_set_templ_value', $fields);
}
?>
<#3259>
<?php
if (!$ilDB->tableExists('adm_set_templ_hide_tab'))
{
	$fields = array(
		'template_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'tab_id' => array(
			'type' => 'text',
			'length' => 80,
			'notnull' => true
			)
	);
	$ilDB->createTable('adm_set_templ_hide_tab', $fields);
}
?>
<#3260>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3261>
<?php
if (!$ilDB->tableColumnExists("il_wiki_data", "page_toc"))
{
	$ilDB->addTableColumn("il_wiki_data", "page_toc", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3262>
<?php
if (!$ilDB->tableColumnExists("il_wiki_page", "blocked"))
{
	$ilDB->addTableColumn("il_wiki_page", "blocked", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3263>
<?php
if (!$ilDB->tableColumnExists("svy_svy", "template_id"))
{
	$ilDB->addTableColumn("svy_svy", "template_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
	));
}
?>
<#3264>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "express_qpool_allowed"))
{
	$ilDB->addTableColumn("tst_tests", "express_qpool_allowed", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1,
		"default" => 0
	));
}
?>
<#3265>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "enabled_view_mode"))
{
	$ilDB->addTableColumn("tst_tests", "enabled_view_mode", array(
		"type" => "text",
		"notnull" => false,
		"length" => 20,
		"default" => 0
	));
}
?>
<#3266>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "template_id"))
{
	$ilDB->addTableColumn("tst_tests", "template_id", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4
	));
}
?>
<#3267>
<?php
if (!$ilDB->tableColumnExists("svy_svy", "pool_usage"))
{
	$ilDB->addTableColumn("svy_svy", "pool_usage", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3268>
<?php
if (!$ilDB->tableColumnExists("svy_qblk", "show_blocktitle"))
{
	$ilDB->addTableColumn("svy_qblk", "show_blocktitle", array(
		"type" => "text",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3269>
<?php
if (!$ilDB->tableColumnExists("tst_tests", "pool_usage"))
{
	$ilDB->addTableColumn("tst_tests", "pool_usage", array(
		"type" => "integer",
		"notnull" => false,
		"length" => 1
	));
}
?>
<#3270>
<?php
if ($ilDB->tableColumnExists("tst_tests", "express_qpool_allowed"))
{
	$ilDB->dropTableColumn("tst_tests", "express_qpool_allowed");
}
?>
<#3271>
<?php
if (!$ilDB->tableColumnExists("il_news_item", "content_text_is_lang_var"))
{
	$ilDB->addTableColumn("il_news_item", "content_text_is_lang_var", array(
		"type" => "integer",
		"length" => 1,
		"notnull" => true,
		"default" => 0
		));
}
?>
<#3272>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Page", "page", "div",
				array());
?>
<#3273>
<?php	
	if(!$ilDB->tableColumnExists('exc_data', 'compl_by_submission'))
	{
		$ilDB->addTableColumn('exc_data', 'compl_by_submission', array(
			'type'		=> 'integer',
			'length'	=> 1,
			'notnull'	=> true,
			'default'	=> 0
		));
	}
?>
<#3274>
<?php
	if(!$ilDB->tableColumnExists('crs_settings', 'auto_noti_disabled'))
	{
		$ilDB->addTableColumn('crs_settings', 'auto_noti_disabled', array(
			'type'		=> 'integer',
			'length'	=> 1,
			'notnull'	=> true,
			'default'	=> 0
		));
	}
?>
<#3275>
<?php
if(!$ilDB->tableExists('il_verification'))
{
	$ilDB->createTable('il_verification',array(
		'id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'type'	=> array(
			'type'	=> 'text',
			'length'=> 100,
			'notnull' => true
		),
		'parameters' => array(
			'type'	=> 'text',
			'length'=> 1000,
			'notnull' => false
		),
		'raw_data'	=> array(
			'type'	=> 'clob',
			'notnull' => false
		)
	));

	$ilDB->addIndex('il_verification', array('id'), 'i1');
}
?>
<#3276>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3277>
<?php	
	if(!$ilDB->tableColumnExists('qpl_qst_fileupload', 'compl_by_submission'))
	{
		$ilDB->addTableColumn('qpl_qst_fileupload', 'compl_by_submission', array(
			'type'		=> 'integer',
			'length'	=> 1,
			'notnull'	=> true,
			'default'	=> 0
		));
	}
?>
<#3278>
<?php
	if(!$ilDB->tableColumnExists('payment_objects', 'subtype'))
	{
		$ilDB->addTableColumn("payment_objects", "subtype", array(
			"type" => "text",
			"length" => 10,
			"notnull" => false,
			"default" => null));
	}
?>
<#3279>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'mail_use_placeholders'))
	{
		$ilDB->addTableColumn("payment_settings", "mail_use_placeholders", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3280>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'mail_billing_text'))
	{
		$ilDB->addTableColumn("payment_settings", "mail_billing_text", array(
			"type" => "clob",
			"notnull" => false,
			"default" => null));
	}
?>
<#3281>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'hide_shop_info'))
	{
		$ilDB->addTableColumn("payment_settings", "hide_shop_info", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3282>
<?php
	if(!$ilDB->tableColumnExists('payment_objects', 'is_special'))
	{
		$ilDB->addTableColumn("payment_objects", "is_special", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3283>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'use_shop_specials'))
	{
		$ilDB->addTableColumn("payment_settings", "use_shop_specials", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0));
	}
?>
<#3284>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3285>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3286>
<?php
if(!$ilDB->tableExists('usr_portfolio'))
{
	$ilDB->createTable('usr_portfolio',array(
		'id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'user_id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 250,
			'notnull' => true
		),
		'description' => array(
			'type'	=> 'clob',
			'notnull' => false
		),
		'is_online' => array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		),
		'is_default' => array(
			'type'	=> 'integer',
			'length'=> 1,
			'notnull' => false
		)
	));
	$ilDB->addPrimaryKey('usr_portfolio', array('id'));
	$ilDB->createSequence('usr_portfolio');
}
?>
<#3287>
<?php
if(!$ilDB->tableExists('usr_portfolio_page'))
{
	$ilDB->createTable('usr_portfolio_page',array(
		'id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'portfolio_id' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
		'title'	=> array(
			'type'	=> 'text',
			'length'=> 250,
			'notnull' => true
		),
		'order_nr' => array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true
		),
	));
	$ilDB->addPrimaryKey('usr_portfolio_page', array('id'));
	$ilDB->createSequence('usr_portfolio_page');
}
?>
<#3288>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3289>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("QuestionImage", "qimg", "img",
				array("margin" => "5px"));
?>
<#3290>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderList", "qordul", "ul",
				array("margin" => "0px",
					"padding" => "0px",
					"list-style" => "none",
					"list-style-position" => "outside"
					));
?>
<#3291>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderListItem", "qordli", "li",
				array(
					"margin-top" => "5px",
					"margin-bottom" => "5px",
					"margin-left" => "0px",
					"margin-right" => "0px",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#D0D0FF",
					"padding" => "10px",
					"cursor" => "move"
					));

?>
<#3292>
<?php

	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ImageDetailsLink", "qimgd", "a",
				array("font-size" => "90%"));

?>
<#3293>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextItem", "qetitem", "a",
				array("text-decoration" => "none",
					"color" => "#000000",
					"padding" => "2px"
					));
?>
<#3294>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextItem:hover", "qetitem", "a",
				array("text-decoration" => "none",
					"color" => "#000000",
					"background-color" => "#D0D0D0"
					));
?>
<#3295>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextSelected", "qetitem", "a",
				array("border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#606060",
					"background-color" => "#9BD9FE"
					));
?>
<#3296>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorTextCorrected", "qetcorr", "span",
				array("text-decoration" => "line-through",
					"color" => "#909090"
					));
?>
<#3297>
<?php
	$set = $ilDB->query("SELECT * FROM style_char ".
		" WHERE ".$ilDB->like("characteristic", "text", "%:hover%")
		);
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$s = substr($rec["characteristic"], strlen($rec["characteristic"]) - 6);
		if ($s == ":hover")
		{
			$ilDB->manipulate("DELETE FROM style_char WHERE ".
				" characteristic = ".$ilDB->quote($rec["characteristic"], "text")
				);
		}
	}
?>
<#3298>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderListHorizontal", "qordul", "ul",
				array("margin" => "0px",
					"padding" => "0px",
					"list-style" => "none",
					"list-style-position" => "outside"
					));
?>
<#3299>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("OrderListItemHorizontal", "qordli", "li",
				array(
					"float" => "left",
					"margin-top" => "5px",
					"margin-bottom" => "5px",
					"margin-right" => "10px",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#D0D0FF",
					"padding" => "10px",
					"cursor" => "move"
					));

?>
<#3300>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3301>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ErrorText", "question", "div",
				array());
?>
<#3302>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("TextSubset", "question", "div",
				array());
?>
<#3303>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3304>
<?php
$ilDB->modifyTableColumn('frm_settings', 'notification_type', array(
	"type" => "text",
	"notnull" => false,
	"length" => 10,
	"default" => null));
?>
<#3305>
<?php

$set = $ilDB->query("SELECT * FROM object_data WHERE type = 'sty'");

while ($rec = $ilDB->fetchAssoc($set))	// all styles
{
	$imgs = array("icon_pin.png", "icon_pin_on.png");
	
	$a_style_id = $rec["obj_id"];
	
	$sty_data_dir = CLIENT_WEB_DIR."/sty";
	ilUtil::makeDir($sty_data_dir);

	$style_dir = $sty_data_dir."/sty_".$a_style_id;
	ilUtil::makeDir($style_dir);

	// create images subdirectory
	$im_dir = $style_dir."/images";
	ilUtil::makeDir($im_dir);

	// create thumbnails directory
	$thumb_dir = $style_dir."/images/thumbnails";
	ilUtil::makeDir($thumb_dir);
	
//	ilObjStyleSheet::_createImagesDirectory($rec["obj_id"]);
	$imdir = CLIENT_WEB_DIR."/sty/sty_".$a_style_id.
			"/images";
	foreach($imgs as $cim)
	{
		if (!is_file($imdir."/".$cim))
		{
			copy("./Services/Style/basic_style/images/".$cim, $imdir."/".$cim);
		}
	}
}
?>
<#3306>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("ContentPopup", "iim", "div",
				array("background-color" => "#FFFFFF",
					"border-color" => "#A0A0A0",
					"border-style" => "solid",
					"border-width" => "2px",
					"padding-top" => "5px",
					"padding-right" => "10px",
					"padding-bottom" => "5px",
					"padding-left" => "10px"
					));
?>
<#3307>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Marker", "marker", "a",
				array("display" => "block",
					"cursor" => "pointer",
					"width" => "27px",
					"height" => "32px",
					"position" => "absolute",
					"background-image" => "icon_pin.png"
					));
?>
<#3308>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Marker:hover", "marker", "a",
				array("background-image" => "icon_pin_on.png"
					));
?>
<#3309>
<?php
	$set = $ilDB->query("SELECT * FROM style_char ".
		" WHERE ".$ilDB->like("characteristic", "text", "%:hover%")
		);
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$s = substr($rec["characteristic"], strlen($rec["characteristic"]) - 6);
		if ($s == ":hover")
		{
			$ilDB->manipulate("DELETE FROM style_char WHERE ".
				" characteristic = ".$ilDB->quote($rec["characteristic"], "text")
				);
		}
	}
?>
<#3310>
<?php
if(!$ilDB->tableExists('usr_portf_acl'))
{
	$fields = array (
		'node_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'object_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
	  );
  $ilDB->createTable('usr_portf_acl', $fields);
  $ilDB->addPrimaryKey('usr_portf_acl', array('node_id', 'object_id'));
}
?>
<#3311>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3312>
<?php
	if(!$ilDB->tableColumnExists('note', 'no_repository'))
	{
		$ilDB->addTableColumn("note", "no_repository", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => false,
			"default" => 0));
	}
?>
<#3313>
<?php
	$ilDB->createTable(
		'ecs_server',
		array(
			'server_id' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'active' => array('type' => 'integer','length' => 1, 'notnull' => false, 'default' => 0),
			'protocol' => array('type' => 'integer','length' => 1, 'notnull' => false, 'default' => 1),
			'server' => array('type' => 'text', 'length' => 255, 'notnull' => false),
			'port' => array('type' => 'integer','length' => 2, 'notnull' => false, 'default' => 1),
			'auth_type' => array('type' => 'integer', 'length' => 1, 'notnull' => false, 'default' => 1),
			'client_cert_path' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'ca_cert_path' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'key_path' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'key_password' => array('type' => 'text', 'length' => 32, 'notnull' => false),
			'cert_serial' => array('type' => 'text', 'length' => 32, 'notnull' => false),
			'polling_time' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'import_id' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'global_role' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0),
			'econtent_rcp' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'user_rcp' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'approval_rcp' => array('type' => 'text', 'length' => 512, 'notnull' => false),
			'duration' => array('type' => 'integer','length' => 4, 'notnull' => false, 'default' => 0)
		));
	$ilDB->createSequence('ecs_server');

?>
<#3314>
<?php
	if(!$ilDB->tableColumnExists('payment_statistic','access_startdate'))
	{
		$ilDB->addTableColumn(
			'payment_statistic',
			'access_startdate',
			array(
				'type'     => 'timestamp',
				'notnull'	=> false
			)
		);
	}
?>
<#3315>
<?php

	// Migration of ecs settings
	$query = 'SELECT * FROM settings WHERE '.
		'module = '.$ilDB->quote('ecs','text');
	$res = $ilDB->query($query);

	$ecs = array();
	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	{
		$ecs[$row->keyword] = $row->value;
	}
	if(count($ecs))
	{
		$ilDB->manipulate('INSERT INTO ecs_server (server_id,active,protocol,server,port,auth_type,client_cert_path,ca_cert_path,'.
			'key_path,key_password,cert_serial,polling_time,import_id,global_role,econtent_rcp,user_rcp,approval_rcp,duration) '.
			'VALUES ('.
			$ilDB->quote($ilDB->nextId('ecs_server'),'integer').', '.
			$ilDB->quote($ecs['active'],'integer').', '.
			$ilDB->quote($ecs['protocol'],'integer').', '.
			$ilDB->quote($ecs['server'],'text').', '.
			$ilDB->quote($ecs['port'],'integer').', '.
			$ilDB->quote(1,'integer').', '.
			$ilDB->quote($ecs['client_cert_path'],'text').', '.
			$ilDB->quote($ecs['ca_cert_path'],'text').', '.
			$ilDB->quote($ecs['key_path'],'text').', '.
			$ilDB->quote($ecs['key_password'],'text').', '.
			$ilDB->quote($ecs['cert_serial'],'text').', '.
			$ilDB->quote($ecs['polling_time'],'integer').', '.
			$ilDB->quote($ecs['import_id'],'integer').', '.
			$ilDB->quote($ecs['global_role'],'integer').', '.
			$ilDB->quote($ecs['econtent_rcp'],'text').', '.
			$ilDB->quote($ecs['user_rcp'],'text').', '.
			$ilDB->quote($ecs['approval_rcp'],'text').', '.
			$ilDB->quote($ecs['duration'],'integer').
			')');
	}
?>
<#3316>
<?php
	$ilDB->modifyTableColumn('cal_entries', 'context_id',
		array("type" => "integer", "length" => 4, "default" => 0, "notnull" => true));
?>
<#3317>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3318>
<?php
	if(!$ilDB->tableColumnExists('payment_settings','show_general_filter'))
	{
		$ilDB->addTableColumn('payment_settings','show_general_filter',
		array(  "type" => "integer",
				"length" => 1,
				"notnull" => false,
				"default" => 0));
	}
?>
<#3319>
<?php
	if(!$ilDB->tableColumnExists('payment_settings','show_topics_filter'))
	{
		$ilDB->addTableColumn('payment_settings','show_topics_filter',
		array(  "type" => "integer",
				"length" => 1,
				"notnull" => false,
				"default" => 0));
	}
?>
<#3320>
<?php
	if(!$ilDB->tableColumnExists('payment_settings','show_shop_explorer'))
	{
		$ilDB->addTableColumn('payment_settings','show_shop_explorer',
		array(  "type" => "integer",
				"length" => 1,
				"notnull" => false,
				"default" => 0));
	}
?>
<#3321>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'ud_invoice_number'))
	{
		$ilDB->addTableColumn('payment_settings', 'ud_invoice_number', array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0));
	}
?>
<#3322>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'invoice_number_text'))
	{
		$ilDB->addTableColumn('payment_settings', 'invoice_number_text', array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false,
			'default' => null));
	}
?>
<#3323>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_start_value'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_start_value', array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true,
			'default' => 0));
	}
?>
<#3324>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_current_value'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_current_value', array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true));
	}
?>
<#3325>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_reset_period'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_reset_period', array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0));
	}
?>
<#3326>
<?php
	if(!$ilDB->tableColumnExists('payment_settings', 'inc_last_reset'))
	{
		$ilDB->addTableColumn('payment_settings', 'inc_last_reset', array(
			'type'	=> 'integer',
			'length'=> 4,
			'notnull' => true));
	}
?>
<#3327>
<?php
	$ilDB->update('payment_settings',
			array('inc_last_reset' => array('integer', time())),
			array('settings_id' => array('integer', 1)));
?>
<#3328>
<?php
		$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'obj_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true
			),
		'yyyy' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => false
			),
		'mm' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'dd' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'hh' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
	);
	$ilDB->createTable('obj_stat', $fields);
	$ilDB->addIndex("obj_stat", array("obj_id", "yyyy", "mm"), "i1");
?>
<#3329>
<?php
		$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'obj_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true
			),
		'tstamp' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'yyyy' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => false
			),
		'mm' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'dd' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'hh' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
	);
	$ilDB->createTable('obj_stat_tmp', $fields);
?>
<#3330>
<?php		
		$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'obj_type' => array(
			'type' => 'text',
			'length' => 10,
			'notnull' => true
			),
		'tstamp' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'yyyy' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => false
			),
		'mm' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'dd' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'hh' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => false
			),
		'read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_read_count' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
		'childs_spent_seconds' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
			),
	);
	$ilDB->createTable('obj_stat_log', $fields);
?>
<#3331>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3332>
<?php
if(!$ilDB->tableExists('cp_datamap'))
{
	$fields = array (
		"sco_node_id" => array (
			"notnull" => true
			,"length" => 4
			,"unsigned" => false
			,"default" => "0"
			,"type" => "integer"
		)
		,"cp_node_id" => array (
			"notnull" => false
			,"length" => 4
			,"unsigned" => false
			,"default" => "0"
			,"type" => "integer"
		)
		,"slm_id" => array (
			"notnull" => true
			,"length" => 4
			,"type" => "integer"
		)
		,"target_id" => array (
			"notnull" => true
			,"length" => 4000
			,"fixed" => false
			,"type" => "text"
		)
		,"read_shared_data" => array (
			"notnull" => false
			,"length" => 1
			,"unsigned" => false
			,"default" => "1"
			,"type" => "integer"
		)
		,"write_shared_data" => array (
			"notnull" => false
			,"length" => 1
			,"unsigned" => false
			,"default" => "1"
			,"type" => "integer"
		)
	);
	$ilDB->createTable("cp_datamap", $fields);
	
	$pk_fields = array("cp_node_id");
	$ilDB->addPrimaryKey("cp_datamap", $pk_fields);
}
?>
<#3333>
<?php
if(!$ilDB->tableExists('adl_shared_data'))
{
	$fields = array (
		"slm_id" => array (
			"notnull" => true
			,"length" => 4
			,"type" => "integer"
		)
		,"user_id" => array (
			"notnull" => true
			,"length" => 4
			,"type" => "integer"
		)
		,"target_id" => array (
			"notnull" => true
			,"length" => 4000
			,"fixed" => false
			,"type" => "text"
		)
		,"store" => array (
			"notnull" => false
			,"type" => "clob"
		)
	);
	$ilDB->createTable("adl_shared_data", $fields);
}
?>
<#3334>
<?php
	$ilDB->addTableColumn("cp_package", "shared_data_global_to_system", array(
		"type" => "integer",
		"notnull" => false,
		"unsigned" => false,
		"default" => "1",
		"length" => 1));
?>
<#3335>
<?php
	if($ilDB->tableExists('cmi_gobjective'))
	{	 
		$ilDB->addTableColumn("cmi_gobjective", "score_raw", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "score_min", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "score_max", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "progress_measure", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
		$ilDB->addTableColumn("cmi_gobjective", "completion_status", array(
			"type" => "text",
			"notnull" => false,
			"length" => 50));
	}
?>
<#3336>
<?php
	$ilDB->addTableColumn("cp_item", "progressweight", array(
		"type" => "text",
		"notnull" => false,
		"default" => "1.0",
		"fixed" => false,
		"length" => 50));
	$ilDB->addTableColumn("cp_item", "completedbymeasure", array(
		"type" => "integer",
		"notnull" => false,
		"unsigned" => false,
		"default" => "0",
		"length" => 1));
	$ilDB->modifyTableColumn("cp_item", "completionthreshold", array("default" => "1.0"));
?>
<#3337>
<?php
	// cmi_objective completion_status from double to text
	$ilDB->addTableColumn("cmi_objective", "completion_status_tmp", array(
		"type" => "text",
		"length" => 32,
		"notnull" => false,
		"default" => null)
	);

	$ilDB->manipulate('UPDATE cmi_objective SET completion_status_tmp = completion_status');
	$ilDB->dropTableColumn('cmi_objective', 'completion_status');
	$ilDB->renameTableColumn("cmi_objective", "completion_status_tmp", "completion_status");
?>
<#3338>
<?php
	if(!$ilDB->tableColumnExists('ecs_server','title'))
	{
		$ilDB->addTableColumn("ecs_server", "title", array(
			"type" => "text",
			"length" => 128,
			"notnull" => false,
			"default" => null)
		);
	}
?>
<#3339>
<?php
if (!$ilDB->tableColumnExists('tst_active', 'importname'))
{
	$ilDB->addTableColumn("tst_active", "importname",
			array(
				"type" => "text",
				"notnull" => false,
			 	"length" => 400,
			 	"fixed" => false));
}
?>
<#3340>
<?php
	if(!$ilDB->tableExists('ecs_part_settings'))
	{
		$fields = array(
			"sid" => array("notnull" => true,"length" => 4,"type" => "integer"),
			"mid" => array("notnull" => true,"length" => 4,"type" => "integer"),
			"export" => array("notnull" => true,"length" => 1,"type" => "integer"),
			"import" => array("notnull" => true,"length" => 1,"type" => "integer"),
			"import_type" => array("notnull" => false,'length' => 1, "type" => "integer")
		);
		$ilDB->createTable("ecs_part_settings", $fields);
	}
?>
<#3341>
<?php
	if(!$ilDB->tableExists('ecs_data_mapping'))
	{
		$fields = array(
			"sid" => array("notnull" => true,"length" => 4,"type" => "integer"),
			"import_type" => array("notnull" => true,"length" => 1,"type" => "integer"),
			"ecs_field" => array("notnull" => false,'length' => 32,"type" => "text"),
			"advmd_id" => array("notnull" => true, "length" => 4, "type" => "integer")
		);
		$ilDB->createTable("ecs_data_mapping", $fields);
		$ilDB->addPrimaryKey('ecs_data_mapping', array('sid','import_type','ecs_field'));
	}
?>
<#3342>
<?php
	if(!$ilDB->tableExists('payment_tmp'))
	{
		$fields = array (
		'keyword' => array ('type' => 'text', 'length'  => 50,'notnull' => true, "fixed" => false),
		'value' => array('type' => 'clob', 'notnull' => false, 'default' => null),
		'scope' =>array ('type' => 'text', 'length'  => 50,'notnull' => false, "default" => null)
		);
		$ilDB->createTable('payment_tmp', $fields);
		$ilDB->addPrimaryKey('payment_tmp', array('keyword'));
	}
?>
<#3343>
<?php
$old = array();
$res = $ilDB->query('SELECT * FROM payment_settings');
$old = $ilDB->fetchAssoc($res);

if($old == NULL)
{
  //use default values
  $old['settings_id'] = '0';
  $old['currency_unit'] = NULL;
  $old['currency_subunit'] =  NULL;
  $old['address'] = NULL;
  $old['bank_data'] =  NULL;
  $old['add_info'] = NULL;
  $old['pdf_path'] =  NULL;
  $old['paypal'] = NULL;
  $old['bmf'] = NULL;
  $old['topics_allow_custom_sorting'] = '0';
  $old['topics_sorting_type'] = '0';
  $old['topics_sorting_direction'] = NULL;
  $old['shop_enabled'] = '0';
  $old['max_hits'] =  '0';
  $old['hide_advanced_search'] =  NULL;
  $old['objects_allow_custom_sorting'] = '0';
  $old['hide_coupons'] =  NULL;
  $old['epay '] = NULL;
  $old['hide_news'] =  NULL;
  $old['mail_use_placeholders'] =  '0';
  $old['mail_billing_text'] = null;
  $old['hide_shop_info'] = '0';
  $old['use_shop_specials'] =  '0';
  $old['ud_invoice_number'] = '0';
  $old['invoice_number_text'] = NULL;
  $old['inc_start_value'] = '0';
  $old['inc_current_value'] = '0';
  $old['inc_reset_period'] = '0';
  $old['inc_last_reset'] = null;
  $old['show_general_filter'] =  '0';
  $old['show_topics_filter'] =  '0';
  $old['show_shop_explorer'] =  '0';
}
foreach($old as $key=>$value)
{
	switch($key)
	{
		case 'paypal':
			$scope = 'paypal';
			break;
		case 'bmf':
			$scope = 'bmf';
			break;
		case 'epay':
			$scope = 'epay';
			break;

		case 'currency_unit':
		case 'currency_subunit':
			$scope = 'currencies';
			break;

		case 'address':
		case 'bank_data':
		case 'add_info':
		case 'pdf_path':
		case 'mail_use_placeholders':
		case 'mail_billing_text':
			$scope = 'invoice';
			break;

		case 'ud_invoice_number':
		case 'ud_shop_specials':
		case 'invoice_number_text':
		case 'inc_start_value':
		case 'inc_currend_value':
		case 'inc_reset_period':
		case 'inc_last_reset':
			$scope = 'invoice_number';
			break;
		case 'topics_allow_custom_sorting':
		case 'topics_sorting_type':
		case 'topics_sorting_direction':
		case 'max_hits':
		case 'hide_advanced_search':
		case 'objects_allow_custom_sorting':
		case 'hide_coupons':
		case 'hide_news':
		case 'hide_shop_info':
		case 'show_general_filter':
		case 'show_topics_filter':
		case 'show_shop_explorer':
		case 'use_shop_specials':
			$scope = 'gui';
			break;

		case 'shop_enabled':
			$scope = 'common';
			break;

		default:
			// for custom settings
			$scope = NULL;
			break;

	}

	$ilDB->insert('payment_tmp',
	array(
		'keyword' => array('text', $key),
		'value' => array('clob', $value),
		'scope' => array('text', $scope)
	));
}
?>
<#3344>
<?php
	if($ilDB->tableExists('payment_settings'))
	{
		$ilDB->dropTable('payment_settings');
	}
	if($ilDB->tableExists('payment_settings_seq'))
	{
		$ilDB->dropTable('payment_settings_seq');
	}
?>
<#3345>
<?php
	if($ilDB->tableExists('payment_tmp'))
	{
		$ilDB->renameTable('payment_tmp', 'payment_settings');
	}
?>
<#3346>
<?php

	$ilDB->dropPrimaryKey('ecs_data_mapping');
	$ilDB->renameTableColumn('ecs_data_mapping','import_type','mapping_type');
	$ilDB->addPrimaryKey('ecs_data_mapping', array('sid','mapping_type','ecs_field'));
?>
<#3347>
<?php
	if (!$ilDB->tableColumnExists("acl_ws", "extended_data"))
	{
		$ilDB->addTableColumn("acl_ws", "extended_data", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 200,
		 	"fixed" => false));
	}
?>
<#3348>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3349>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3350>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Correct", "qover", "div",
				array(
					"margin-top" => "20px",
					"margin-bottom" => "20px",
					"padding-top" => "10px",
					"padding-right" => "60px",
					"padding-bottom" => "10px",
					"padding-left" => "30px",
					"background-color" => "#E7FFE7",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#808080"
				));
?>
<#3351>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("Incorrect", "qover", "div",
				array(
					"margin-top" => "20px",
					"margin-bottom" => "20px",
					"padding-top" => "10px",
					"padding-right" => "60px",
					"padding-bottom" => "10px",
					"padding-left" => "30px",
					"background-color" => "#FFE7E7",
					"border-width" => "1px",
					"border-style" => "solid",
					"border-color" => "#808080"
				));
?>
<#3352>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("StatusMessage", "qover", "div",
				array(
					"padding-bottom" => "7px"
				));
?>
<#3353>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("WrongAnswersMessage", "qover", "div",
				array(
				));
?>
<#3354>
<?php
	if (!$ilDB->tableColumnExists("ecs_import", "server_id"))
	{
		$ilDB->addTableColumn("ecs_import", "server_id", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 4,
		 	"default" => 0)
		);
	}
	$ilDB->dropPrimaryKey('ecs_import');
	$ilDB->addPrimaryKey('ecs_import', array('server_id','obj_id'));
?>
<#3355>
<?php

	$res = $ilDB->query('SELECT server_id FROM ecs_server');

	if($res->numRows())
	{
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		$query = 'UPDATE ecs_import SET server_id = '.$ilDB->quote($row->server_id);
		$ilDB->manipulate($query);
	}
?>
<#3356>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3357>
<?php
$ilDB->addTableColumn("sahs_lm", "localization", array(
	"type" => "text",
	"notnull" => false,
	"length" => 2
));
?>

<#3358>
<?php
	if (!$ilDB->tableColumnExists("ecs_export", "server_id"))
	{
		$ilDB->addTableColumn("ecs_export", "server_id", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 4,
		 	"default" => 0)
		);
	}
	$ilDB->dropPrimaryKey('ecs_export');
	$ilDB->addPrimaryKey('ecs_export', array('server_id','obj_id'));
?>
<#3359>
<?php

	$res = $ilDB->query('SELECT server_id FROM ecs_server');

	if($res->numRows())
	{
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		$query = 'UPDATE ecs_export SET server_id = '.$ilDB->quote($row->server_id);
		$ilDB->manipulate($query);
	}
?>
<#3360>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3361>
<?php
	if (!$ilDB->tableColumnExists("ecs_part_settings", "title"))
	{
		$ilDB->addTableColumn("ecs_part_settings", "title", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 255
			)
		);
		$ilDB->addTableColumn("ecs_part_settings", "cname", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 255
			)
		);
	}
?>
<#3362>
<?php
	if (!$ilDB->tableColumnExists("exc_assignment", "type"))
	{
		$ilDB->addTableColumn("exc_assignment", "type", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 1,
			"default" => 1
			)
		);
	}
?>
<#3363>
<?php
	if(!$ilDB->tableExists('ecs_community'))
	{
		$fields = array (
			'sid'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true),
			'cid'   => array ('type' => 'integer', 'length' => 4, 'notnull' => true),
			'own_id'    => array ('type' => 'integer', 'length'  => 4,"notnull" => true),
			'cname'  => array ('type' => 'text', 'length'  => 255,"notnull" => false),
			'mids' => array('type' => 'text','length' => 512,'notnull' => false)
	  );
	  $ilDB->createTable('ecs_community', $fields);
	  $ilDB->addPrimaryKey('ecs_community', array('sid','cid'));
	}
?>
<#3364>
<?php
	if (!$ilDB->tableColumnExists("ecs_events", "server_id"))
	{
		$ilDB->addTableColumn("ecs_events", "server_id", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 4,
		 	"default" => 0)
		);
	}
?>
<#3365>
<?php

	$res = $ilDB->query('SELECT server_id FROM ecs_server');

	if($res->numRows())
	{
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		$query = 'UPDATE ecs_events SET server_id = '.$ilDB->quote($row->server_id);
		$ilDB->manipulate($query);
	}
?>
<#3366>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3367>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETreeList", "rte_tul", "ul",
				array());
	ilDBUpdate3136::addStyleClass("RTETreeItem", "rte_tli", "li",
				array());
	ilDBUpdate3136::addStyleClass("RTETreeExpanded", "rte_texp", "a",
				array());
	ilDBUpdate3136::addStyleClass("RTETreeCollapsed", "rte_texp", "a",
				array());
?>
<#3368>
<?php
$ilDB->manipulate("UPDATE style_parameter SET ".
	" tag = ".$ilDB->quote("div", "integer").
	" WHERE type = ".$ilDB->quote("rte_node", "text")
	);
?>
<#3369>
<?php
$ilDB->manipulate("UPDATE style_parameter SET ".
	" tag = ".$ilDB->quote("div", "integer").
	" WHERE type = ".$ilDB->quote("rte_status", "text")
	);
?>
<#3370>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETreeCurrent", "rte_tlink", "a",
				array());
?>
<#3371>
<?php
	if (!$ilDB->tableColumnExists("usr_portfolio_page", "type"))
	{
		$ilDB->addTableColumn("usr_portfolio_page", "type", array(
			"type" => "integer",
			"notnull" => true,
		 	"length" => 1,
		 	"default" => 1)
		);
	}
?>
<#3372>
<?php
	// remove existing portfolios which are not based on object_data
	$ilDB->manipulate("DELETE FROM usr_portfolio");
	$ilDB->manipulate("DELETE FROM usr_portfolio_page");

	// remove obsolete portfolio columns
	if($ilDB->tableColumnExists('usr_portfolio','user_id'))
	{
		$ilDB->dropTableColumn('usr_portfolio', 'user_id');
	}
	if($ilDB->tableColumnExists('usr_portfolio','title'))
	{
		$ilDB->dropTableColumn('usr_portfolio', 'title');
	}
	if($ilDB->tableColumnExists('usr_portfolio','description'))
	{
		$ilDB->dropTableColumn('usr_portfolio', 'description');
	}
	$ilDB->dropSequence('usr_portfolio');
?>
<#3373>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3374>
<?php
	if(!$ilDB->tableColumnExists('ecs_server','auth_user'))
	{
		$ilDB->addTableColumn("ecs_server", "auth_user", array(
			"type" => "text",
			"length" => 32,
			"notnull" => false,
			"default" => null)
		);
	}
?>
<#3375>
<?php
	if(!$ilDB->tableColumnExists('ecs_server','auth_pass'))
	{
		$ilDB->addTableColumn("ecs_server", "auth_pass", array(
			"type" => "text",
			"length" => 32,
			"notnull" => false,
			"default" => null)
		);
	}
?>

<#3376>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 0)
	{
		// skill tree
		$fields = array(
				'skl_tree_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'child' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'parent' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => false,
						'default' => null
				),
				'lft' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'rgt' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'depth' => array(
						'type' => 'integer',
						'length' => 2,
						'notnull' => true,
						'default' => 0
				)
		);
		$ilDB->createTable('skl_tree', $fields);
	}
?>
<#3377>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 1)
	{
		// skill tree nodes
		$fields = array(
				'obj_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0

				),
				'title' => array(
						'type' => 'text',
						'length' => 200,
						'notnull' => false
				),
				'type' => array(
						'type' => 'text',
						'length' => 4,
						'fixed' => true,
						'notnull' => false
				),
				'create_date' => array(
						'type' => 'timestamp',
						'notnull' => false
				),
				'last_update' => array(
						'type' => 'timestamp',
						'notnull' => false
				)
		);
		$ilDB->createTable('skl_tree_node', $fields);
		$ilDB->createSequence('skl_tree_node');
		$ilDB->addPrimaryKey("skl_tree_node", array("obj_id"));
	}
?>
<#3378>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 2)
	{
		// add new type "skmg" for skill management
		$nid = $ilDB->nextId("object_data");
		$ilDB->manipulate("INSERT INTO object_data ".
			"(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
			$ilDB->quote($nid, "integer").",".
			$ilDB->quote("typ", "text").",".
			$ilDB->quote("skmg", "text").",".
			$ilDB->quote("Skill Management", "text").",".
			$ilDB->quote(-1, "integer").",".
			$ilDB->now().",".
			$ilDB->now().
			")");
		$typ_id = $nid;

		// add skill management node in settings folder
		$nid = $ilDB->nextId("object_data");
		$ilDB->manipulate("INSERT INTO object_data ".
			"(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
			$ilDB->quote($nid, "integer").",".
			$ilDB->quote("skmg", "text").",".
			$ilDB->quote("__SkillManagement", "text").",".
			$ilDB->quote("Skill Management", "text").",".
			$ilDB->quote(-1, "integer").",".
			$ilDB->now().",".
			$ilDB->now().
			")");

		$nrid = $ilDB->nextId("object_reference");
		$ilDB->manipulate("INSERT INTO object_reference ".
			"(ref_id, obj_id) VALUES (".
			$ilDB->quote($nrid, "integer").",".
			$ilDB->quote($nid, "integer").
			")");

		// put in tree
		$tree = new ilTree(ROOT_FOLDER_ID);
		$tree->insertNode($nrid, SYSTEM_FOLDER_ID);


		$set = $ilDB->query("SELECT obj_id FROM object_data WHERE ".
			" type = ".$ilDB->quote("typ", "text")." AND ".
			" title = ".$ilDB->quote("skmg", "text")
			);
		$rec = $ilDB->fetchAssoc($set);
		$typ_id = $rec["obj_id"];

		// add rbac operations
		// 1: edit_permissions, 2: visible, 3: read, 4:write
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(1, "integer").
			")");
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(2, "integer").
			")");
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(3, "integer").
			")");
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(4, "integer").
			")");
	}
?>
<#3379>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 4)
	{
		// add skill tree and root node
		$nid = $ilDB->nextId("skl_tree_node");
		$ilDB->manipulate("INSERT INTO skl_tree_node ".
			"(obj_id, type, title, create_date) VALUES (".
			$ilDB->quote($nid, "integer").",".
			$ilDB->quote("skrt", "text").",".
			$ilDB->quote("Skill Tree Root Node", "text").",".
			$ilDB->now().
			")");

		$skill_tree = new ilTree(1);
		$skill_tree->setTreeTablePK("skl_tree_id");
		$skill_tree->setTableNames('skl_tree', 'skl_tree_node');
		$skill_tree->addTree(1, $nid);
	}
?>
<#3380>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 6)
	{
		$fields = array(
				'id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'skill_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'nr' => array(
						'type' => 'integer',
						'length' => 2,
						'notnull' => true
				),
				'title' => array(
						'type' => 'text',
						'length' => 200,
						'notnull' => false
				),
				'description' => array(
						'type' => 'clob'
				)
			);
		$ilDB->createTable('skl_level', $fields);
		$ilDB->createSequence('skl_level');
		$ilDB->addPrimaryKey("skl_level", array("id"));
	}
?>
<#3381>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 8)
	{
		$ilDB->addTableColumn("skl_level", "trigger_ref_id", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 4,
			"default" => 0
			));
		$ilDB->addTableColumn("skl_level", "trigger_obj_id", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 4,
			"default" => 0
			));
	}
?>
<#3382>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 9)
	{
		$fields = array(
				'level_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'user_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'status_date' => array(
						'type' => 'timestamp',
						'notnull' => true
				),
				'skill_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'status' => array(
						'type' => 'integer',
						'length' => 1,
						'notnull' => true
				),
				'valid' => array(
						'type' => 'integer',
						'length' => 1,
						'notnull' => true,
						'default' => 0
				),
				'trigger_ref_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'trigger_obj_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'trigger_title' => array(
						'type' => 'text',
						'length' => 200,
						'notnull' => false
				)
			);
		$ilDB->createTable('skl_user_skill_level', $fields);
		$ilDB->addIndex("skl_user_skill_level", array("skill_id"), "isk");
		$ilDB->addIndex("skl_user_skill_level", array("level_id"), "ilv");
		$ilDB->addIndex("skl_user_skill_level", array("user_id"), "ius");
		$ilDB->addIndex("skl_user_skill_level", array("status_date"), "isd");
		$ilDB->addIndex("skl_user_skill_level", array("status"), "ist");
		$ilDB->addIndex("skl_user_skill_level", array("valid"), "ivl");
	}
?>
<#3383>
<?php
	$setting = new ilSetting();
	$sk_step = (int) $setting->get('sk_db');
	if ($sk_step <= 10)
	{
		$fields = array(
				'level_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'user_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'status_date' => array(
						'type' => 'timestamp',
						'notnull' => true
				),
				'skill_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'trigger_ref_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'trigger_obj_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true
				),
				'trigger_title' => array(
						'type' => 'text',
						'length' => 200,
						'notnull' => false
				)
			);
		$ilDB->createTable('skl_user_has_level', $fields);
		$ilDB->addPrimaryKey('skl_user_has_level',
			array("level_id", "user_id"));
	}
?>
<#3384>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3385>
<?php
	$setting = new ilSetting();
	$cd_step = (int) $setting->get('cd_db');
	if ($cd_step <= 3)
	{
		$ilDB->addTableColumn("skl_tree_node", "self_eval", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0
			));
	}
?>
<#3386>
<?php
	$setting = new ilSetting();
	$cd_step = (int) $setting->get('cd_db');
	if ($cd_step <= 5)
	{
		// skill self evaluation table
		$fields = array(
				'id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'user_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'top_skill_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'created' => array(
						'type' => 'timestamp',
						'notnull' => true,
				),
				'last_update' => array(
						'type' => 'timestamp',
						'notnull' => true,
				)
		);

		$ilDB->createTable('skl_self_eval', $fields);
		$ilDB->addPrimaryKey("skl_self_eval", array("id"));
		$ilDB->createSequence('skl_self_eval');
	}
?>
<#3387>
<?php
	$setting = new ilSetting();
	$cd_step = (int) $setting->get('cd_db');
	if ($cd_step <= 6)
	{
		// skill self evaluation table
		$fields = array(
				'self_eval_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'skill_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				),
				'level_id' => array(
						'type' => 'integer',
						'length' => 4,
						'notnull' => true,
						'default' => 0
				)
		);

		$ilDB->createTable('skl_self_eval_level', $fields);
		$ilDB->addPrimaryKey("skl_self_eval_level",
			array("self_eval_id", "skill_id"));
	}
?>
<#3388>
<?php
	if(!$ilDB->tableColumnExists('tst_tests','online_status'))
	{
		$ilDB->addTableColumn("tst_tests", "online_status", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0
		));
	}
?>
<#3389>
<?php

	$query = "
		UPDATE		tst_tests
		SET			online_status = 1
		WHERE		complete = 1
	";

?>
<#3390>
<?php

	$setting = new ilSetting();
	$elb_db = $setting->get("elb_db", -1);

	if( $elb_db == -1 )
	{
		$query = "
			UPDATE		tst_tests
			SET			online_status = 1
			WHERE		complete = 1
		";

		$ilDB->manipulate($query);
	}
?>

<#3391>
<?php
	// skill template reference
	$fields = array(
			'skl_node_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'templ_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			)
	);

	$ilDB->createTable('skl_templ_ref', $fields);
	$ilDB->addPrimaryKey("skl_templ_ref", array("skl_node_id"));
?>
<#3392>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3393>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3394>
<?php

	// personal skill
	$fields = array(
			'user_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'skill_node_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			)
	);

	$ilDB->createTable("skl_personal_skill", $fields);
	$ilDB->addPrimaryKey("skl_personal_skill", array("user_id", "skill_node_id"));
?>
<#3395>
<?php
	if(!$ilDB->sequenceExists('ecs_container_mapping'))
	{
		$res = $ilDB->query('SELECT mapping_id FROM ecs_container_mapping ');
		$rows = $res->numRows();

		$ilDB->createSequence('ecs_container_mapping',++$rows);
	}
?>
<#3396>
<?php

	// assigned materials
	$fields = array(
			'user_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'top_skill_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'skill_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'level_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'wsp_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			)
	);

	$ilDB->createTable("skl_assigned_material", $fields);
	$ilDB->addPrimaryKey("skl_assigned_material", array("user_id", "top_skill_id", "skill_id", "level_id", "wsp_id"));
?>
<#3397>
<?php
		$ilDB->addTableColumn("frm_settings", "mark_mod_posts", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3398>
<?php

	if(!$ilDB->tableColumnExists('cal_entries','notification'))
	{
		$ilDB->addTableColumn(
			'cal_entries',
			'notification',
			array(
				'type' 		=> 'integer',
				'length' 	=> 1,
				'notnull'	=> true,
				'default'	=> 0
			)
		);
	}
?>
<#3399>
<?php
	// assigned materials
	$fields = array(
			'user_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'nr' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'ref_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'type' => array(
					'type' => 'text',
					'length' => 10,
					'notnull' => true,
			),
			'sub_obj_id' => array(
					'type' => 'text',
					'length' => 40,
					'notnull' => false
			),
			'goto_link' => array(
					'type' => 'text',
					'length' => 1000,
					'notnull' => false
			)
	);

	$ilDB->createTable("last_visited", $fields);
	$ilDB->addPrimaryKey("last_visited", array("user_id", "nr"));
?>
<#3400>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3401>
<?php

	// Crs typ-id
	$query = 'SELECT obj_id FROM object_data WHERE type = '.$ilDB->quote('typ','text').' AND title = '.$ilDB->quote('crs');
	$res = $ilDB->query($query);
	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	$typ_id = $row->obj_id;

	// operation create_crsr
	$query = 'SELECT * FROM rbac_operations WHERE operation = '.$ilDB->quote('create_crsr');
	$res = $ilDB->query($query);
	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	$crs_create_id = $row->ops_id;

	// operation create_catr
	$query = 'SELECT * FROM rbac_operations WHERE operation = '.$ilDB->quote('create_catr');
	$res = $ilDB->query($query);
	$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
	$cat_create_id = $row->ops_id;

	$query = 'SELECT * FROM rbac_ta WHERE typ_id = '.$ilDB->quote($typ_id,'integer').' AND ops_id = '.$ilDB->quote($crs_create_id,'integer');
	$res = $ilDB->query($query);
	if(!$res->numRows())
	{
		$query = 'INSERT INTO rbac_ta (typ_id,ops_id) VALUES( '.$ilDB->quote($typ_id,'integer').', '.$ilDB->quote($crs_create_id,'integer').')';
		$ilDB->manipulate($query);
	}

	$query = 'SELECT * FROM rbac_ta WHERE typ_id = '.$ilDB->quote($typ_id,'integer').' AND ops_id = '.$ilDB->quote($cat_create_id,'integer');
	$res = $ilDB->query($query);
	if(!$res->numRows())
	{
		$query = 'INSERT INTO rbac_ta (typ_id,ops_id) VALUES( '.$ilDB->quote($typ_id,'integer').', '.$ilDB->quote($cat_create_id,'integer').')';
		$ilDB->manipulate($query);
	}
?>
<#3402>
<?php
	$ilDB->dropTableColumn("sahs_lm", "final_lm_page");
	$ilDB->dropTableColumn("sahs_lm", "final_sco_page");
?>
<#3403>
<?php

		$ilDB->createTable('didactic_tpl_settings',
			array(
				"id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				),
				"enabled" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				),
				"type" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				),
				"title" => array(
					"type" => "text", 
					"length" => 64,
					"notnull" => false
				),
				"description" => array(
					"type" => "text",
					"length" => 512,
					"notnull" => false
				)
			)
		);
		$ilDB->createSequence('didactic_tpl_settings');
		$ilDB->addPrimaryKey('didactic_tpl_settings',array('id'));
?>
<#3404>
<?php

		$ilDB->createTable('didactic_tpl_sa',
			array(
				"id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				),
				"obj_type" => array(
					"type" => "text",
					"length" => 8,
					"notnull" => false
				)
			)
		);
		$ilDB->addPrimaryKey('didactic_tpl_sa',array('id','obj_type'));
?>
<#3405>
<?php
	if(!$ilDB->tableColumnExists('il_blog','bg_color'))
	{
		$ilDB->addTableColumn(
			'il_blog',
			'bg_color',
			array(
				'type' 		=> 'text',
				'length' 	=> 6,
				'notnull'	=> false,
				'fixed'		=> true
			)
		);
	}
	if(!$ilDB->tableColumnExists('il_blog','font_color'))
	{
		$ilDB->addTableColumn(
			'il_blog',
			'font_color',
			array(
				'type' 		=> 'text',
				'length' 	=> 6,
				'notnull'	=> false,
				'fixed'		=> true
			)
		);
	}
	if(!$ilDB->tableColumnExists('il_blog','img'))
	{
		$ilDB->addTableColumn(
			'il_blog',
			'img',
			array(
				'type' 		=> 'text',
				'length' 	=> 255,
				'notnull'	=> false
			)
		);
	}
?>
<#3406>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3407>
<?php
		$ilDB->createTable('didactic_tpl_a',
			array(
				"id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				),
				"tpl_id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				),
				"type_id" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				)
			)
		);
		
		$ilDB->createSequence('didactic_tpl_a');
		$ilDB->addPrimaryKey('didactic_tpl_a',array('id'));
?>
<#3408>
<?php
		$ilDB->createTable('didactic_tpl_alp',
			array(
				"action_id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				),
				"filter_type" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				),
				"template_type" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				),
				"template_id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				)
			)
		);

		$ilDB->addPrimaryKey('didactic_tpl_alp',array('action_id'));
?>
<#3409>
<?php
// register new object type 'blga' for blog administration
 $id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "typ", "blga", "Blog administration", -1, ilUtil::now(), ilUtil::now()));
$typ_id = $id;

// create object data entry
$id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "blga", "__BlogAdministration", "Blog Administration", -1, ilUtil::now(), ilUtil::now()));

// create object reference entry
$ref_id = $ilDB->nextId('object_reference');
$res = $ilDB->manipulateF("INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($ref_id, $id));

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($ref_id, SYSTEM_FOLDER_ID);

// add rbac operations
// 1: edit_permissions, 2: visible, 3: read, 4:write
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 1));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 2));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 3));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 4));
?>
<#3410>
<?php
// register new object type 'prfa' for portfolio administration
 $id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "typ", "prfa", "Portfolio administration", -1, ilUtil::now(), ilUtil::now()));
$typ_id = $id;

// create object data entry
$id = $ilDB->nextId("object_data");
$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "prfa", "__PortfolioAdministration", "Portfolio Administration", -1, ilUtil::now(), ilUtil::now()));

// create object reference entry
$ref_id = $ilDB->nextId('object_reference');
$res = $ilDB->manipulateF("INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($ref_id, $id));

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($ref_id, SYSTEM_FOLDER_ID);

// add rbac operations
// 1: edit_permissions, 2: visible, 3: read, 4:write
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 1));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 2));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 3));
$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
	array("integer", "integer"),
	array($typ_id, 4));
?>
<#3411>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3412>
<?php
	if(!$ilDB->tableColumnExists('usr_portfolio','bg_color'))
	{
		$ilDB->addTableColumn(
			'usr_portfolio',
			'bg_color',
			array(
				'type' 		=> 'text',
				'length' 	=> 6,
				'notnull'	=> false,
				'fixed'		=> true
			)
		);
	}
	if(!$ilDB->tableColumnExists('usr_portfolio','font_color'))
	{
		$ilDB->addTableColumn(
			'usr_portfolio',
			'font_color',
			array(
				'type' 		=> 'text',
				'length' 	=> 6,
				'notnull'	=> false,
				'fixed'		=> true
			)
		);
	}
	if(!$ilDB->tableColumnExists('usr_portfolio','img'))
	{
		$ilDB->addTableColumn(
			'usr_portfolio',
			'img',
			array(
				'type' 		=> 'text',
				'length' 	=> 255,
				'notnull'	=> false
			)
		);
	}
?>
<#3413>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3414>
<?php
	require_once 'Modules/Chatroom/classes/class.ilChatroomFormFactory.php';
	require_once 'Modules/Chatroom/classes/class.ilChatroom.php';
	require_once 'Modules/Chatroom/classes/class.ilChatroomInstaller.php';
	ilChatroomInstaller::install();

	require_once 'Services/Notifications/classes/class.ilNotificationSetupHelper.php';
	ilNotificationSetupHelper::setupTables();

	$settings = new ilSetting('notifications');
	$settings->set( 'enable_mail', 1 );

	$ilDB->insert(
		'notification_usercfg',
		array(
		    'usr_id' => array('integer', -1),
		    'module' => array('text', 'chat_invitation'),
		    'channel' => array('text', 'mail')
		)
	);
	$ilDB->insert(
		'notification_usercfg',
		array(
		    'usr_id' => array('integer', -1),
		    'module' => array('text', 'chat_invitation'),
		    'channel' => array('text', 'osd')
		)
	);
	$ilDB->insert(
		'notification_usercfg',
		array(
		    'usr_id' => array('integer', -1),
		    'module' => array('text', 'osd_main'),
		    'channel' => array('text', 'osd')
		)
	);

	$ilDB->manipulate("UPDATE notification_channels SET config_type = 'set_by_admin'");
?>
<#3415>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3416>
<?php
	$ilDB->manipulate(
			'INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES ('.
			$ilDB->quote(4,'integer').', '.
			$ilDB->quote('chtr','text').', '.
			$ilDB->quote(2,'integer').', '.
			$ilDB->quote(8,'integer') .
			')');
	$ilDB->manipulate(
			'INSERT INTO rbac_templates (rol_id, type, ops_id, parent) VALUES ('.
			$ilDB->quote(4,'integer').', '.
			$ilDB->quote('chtr','text').', '.
			$ilDB->quote(3,'integer').', '.
			$ilDB->quote(8,'integer') .
			')');
?>
<#3417>
<?php
if(!$ilDB->tableExists('usr_account_codes'))
{
	$fields = array (
		'code_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0),

		'code' => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 50,
			'fixed' => false),

	 	'valid_until' => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 10),

		'generated' => array(
			'type' => 'integer',
			'notnull' => false,
			'length' => 4,
			'default' => 0),

		'used' => array(
			'type' => 'integer',
			'notnull' => true,
			'length' => 4,
			'default' => 0)
	);
	$ilDB->createTable('usr_account_codes', $fields);
	$ilDB->addPrimaryKey('usr_account_codes', array('code_id'));
	$ilDB->addIndex('usr_account_codes', array('code'), 'i1');
	$ilDB->createSequence("usr_account_codes");
}
?>
<#3418>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3419>
<?php

	// fix missing mep_data entries
	$set = $ilDB->query("SELECT * FROM object_data ".
		" WHERE type = ".$ilDB->quote("mep", "text"));
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$set2 = $ilDB->query("SELECT * FROM mep_data ".
			" WHERE id = ".$ilDB->quote($rec["obj_id"], "integer"));
		if ($rec2 = $ilDB->fetchAssoc($set2))
		{
			// everything ok
		}
		else
		{
			$q = "INSERT INTO mep_data ".
				"(id, default_width, default_height) VALUES (".
				$ilDB->quote($rec["obj_id"], "integer").", ".
				$ilDB->quote(null, "integer").", ".
				$ilDB->quote(null, "integer").
				")";
			$ilDB->manipulate($q);
		}
	}
?>
<#3420>
<?php
	$ilDB->query("UPDATE notification_types SET config_type = 'set_by_admin' WHERE type_name = 'chat_invitation'");
	$ilDB->query("UPDATE notification_types SET config_type = 'set_by_admin' WHERE type_name = 'osd_maint'");
?>
<#3421>
<?php
	require_once 'Modules/Chatroom/classes/class.ilChatroom.php';
	require_once 'Modules/Chatroom/classes/class.ilChatroomInstaller.php';
	ilChatroomInstaller::createMissinRoomSettingsForConvertedObjects();
?>
<#3422>
<?php

	if(!$ilDB->tableColumnExists('grp_settings','view_mode'))
	{
		$ilDB->addTableColumn(
			'grp_settings',
			'view_mode',
			array(
				'type' 		=> 'integer',
				'length' 	=> 1,
				'notnull'	=> true,
				'default'		=> 6 
			)
		);
	}
?>
<#3423>
<?php
	$query = "SELECT obj_id FROM object_data WHERE type = 'typ' AND title = 'chta'";
	$rset = $ilDB->query( $query );
	$row = $ilDB->fetchAssoc($rset);

	if (!$row) {

		$typ_id = $ilDB->nextId("object_data");
		$ilDB->manipulate("INSERT INTO object_data ".
			"(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote("typ", "text").",".
			$ilDB->quote("chta", "text").",".
			$ilDB->quote("Chatroom Administration Type", "text").",".
			$ilDB->quote(-1, "integer").",".
			$ilDB->now().",".
			$ilDB->now().
			")");

		// add rbac operations
		// 1: edit_permissions, 2: visible, 3: read, 4:write
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(1, "integer").
			")");
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(2, "integer").
			")");
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(3, "integer").
			")");
		$ilDB->manipulate("INSERT INTO rbac_ta ".
			"(typ_id, ops_id) VALUES (".
			$ilDB->quote($typ_id, "integer").",".
			$ilDB->quote(4, "integer").
			")");
	}
	
?>
<#3424>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','open_mode'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'open_mode',
			array(
				'type' 		=> 'integer',
				'length' 	=> 1,
				'notnull'	=> true,
				'default'	=> 0 
			)
		);
		$ilDB->query("UPDATE sahs_lm SET open_mode = 5");
	}
?>
<#3425>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','width'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'width',
			array(
				'type' 		=> 'integer',
				'length' 	=> 2,
				'notnull'	=> true,
				'default'	=> 950 
			)
		);
		$ilDB->query("UPDATE sahs_lm SET width = 950");
	}
?>
<#3426>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','height'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'height',
			array(
				'type' 		=> 'integer',
				'length' 	=> 2,
				'notnull'	=> true,
				'default'	=> 650 
			)
		);
		$ilDB->query("UPDATE sahs_lm SET height = 650");
	}
?>

<#3427>
<?php

		$ilDB->createTable('didactic_tpl_fp',
			array(
				"pattern_id" => array(
					"type" => "integer",
					"length" => 4,
					"notnull" => true
				),
				"pattern_type" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				),
				"pattern_sub_type" => array(
					"type" => "integer",
					"length" => 1,
					"notnull" => true
				),
				"pattern" => array(
					"type" => "text",
					"length" => 64,
					"notnull" => false
				)
			)
		);
		$ilDB->createSequence('didactic_tpl_fp');
		$ilDB->addPrimaryKey('didactic_tpl_fp',array('pattern_id'));
?>

<#3428>
<?php

	$ilDB->addTableColumn(
		"didactic_tpl_fp",
		"parent_id",
		array(
			"type" => "integer",
			"notnull" => true,
			"length" => 4
		)
	);

	$ilDB->addTableColumn(
		"didactic_tpl_fp",
		"parent_type",
		array(
			"type" => "text",
			"notnull" => false,
			"length" => 32
		)
	);
?>

<#3429>
<?php
	$ilDB->createTable('didactic_tpl_alr',
		array(
			"action_id" => array(
				"type" => "integer",
				"length" => 4,
				"notnull" => true
			),
			"role_template_id" => array(
				"type" => "integer",
				"length" => 1,
				"notnull" => true
			)
		)
	);

	$ilDB->addPrimaryKey('didactic_tpl_alr',array('action_id'));
?>

<#3430>
<?php
	if(!$ilDB->tableColumnExists('skl_tree_node','order_nr'))
	{
		$ilDB->addTableColumn(
			'skl_tree_node',
			'order_nr',
			array(
				'type' 		=> 'integer',
				'length' 	=> 4,
				'notnull'	=> true,
				'default'		=> 0
			)
		);
	}
?>
<#3431>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3432>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','auto_continue'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'auto_continue',
			array(
				"type" => "text",
				'length' => 1,
				"notnull" => true,
				"default" => 'n'
			)
		);
		$ilDB->query("UPDATE sahs_lm SET auto_continue = 'n'");
	}
?>

<#3433>
<?php
	$ilDB->createTable('didactic_tpl_objs',
		array(
			"obj_id" => array(
				"type" => "integer",
				"length" => 4,
				"notnull" => true
			),
			"tpl_id" => array(
				"type" => "integer",
				"length" => 4,
				"notnull" => true
			)
		)
	);

	$ilDB->addPrimaryKey('didactic_tpl_objs',array('obj_id','tpl_id'));
?>
<#3434>
<?php
	if(!$ilDB->tableColumnExists('usr_portfolio','comments'))
	{
		$ilDB->addTableColumn(
			'usr_portfolio',
			'comments',
			array(
				'type'	=> 'integer',
				'length'=> 1,
				'notnull' => false
			)
		);
	}
?>
<#3435>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3436>
<?php
	$ilDB->modifyTableColumn('didactic_tpl_alr', 'role_template_id',
		array("type" => "integer", "length" => 4, "notnull" => false));
?>
<#3437>
<?php
	$ilDB->createTable('didactic_tpl_abr',
		array(
			"action_id" => array(
				"type" => "integer",
				"length" => 4,
				"notnull" => true
			),
			"filter_type" => array(
				"type" => "integer",
				"length" => 1,
				"notnull" => true
			)
		)
	);

	$ilDB->addPrimaryKey('didactic_tpl_abr',array('action_id'));
?>
<#3438>
<?php
	if(!$ilDB->tableColumnExists('il_blog','ppic'))
	{
		$ilDB->addTableColumn(
			'il_blog',
			'ppic',
			array(
				'type'	=> 'integer',
				'length'=> 1,
				'notnull' => false
			)
		);
	}
?>
<#3439>
<?php
	if(!$ilDB->tableColumnExists('usr_portfolio','ppic'))
	{
		$ilDB->addTableColumn(
			'usr_portfolio',
			'ppic',
			array(
				'type'	=> 'integer',
				'length'=> 1,
				'notnull' => false
			)
		);
	}
?>
<#3440>
<?php
	$ilDB->addTableColumn("skl_assigned_material", "tref_id", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 4,
		"default" => 0));
	$ilDB->dropPrimaryKey("skl_assigned_material");
	$ilDB->addPrimaryKey("skl_assigned_material",
		array("user_id", "top_skill_id", "tref_id", "skill_id", "level_id", "wsp_id"));
?>
<#3441>
<?php
	$ilDB->addTableColumn("skl_self_eval_level", "tref_id", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 4,
		"default" => 0));
	$ilDB->addTableColumn("skl_self_eval_level", "user_id", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 4,
		"default" => 0));
	$ilDB->addTableColumn("skl_self_eval_level", "top_skill_id", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 4,
		"default" => 0));
	$ilDB->addTableColumn("skl_self_eval_level", "last_update", array(
		"type" => "timestamp",
		"notnull" => false));
	$ilDB->dropPrimaryKey("skl_self_eval_level");
	$ilDB->dropTableColumn("skl_self_eval_level", "self_eval_id");
	$ilDB->addPrimaryKey("skl_self_eval_level",
		array("user_id", "top_skill_id", "tref_id", "skill_id"));
?>
<#3442>
<?php
	if(!$ilDB->tableColumnExists('page_layout','mod_scorm'))
	{
		$ilDB->addTableColumn(
			'page_layout',
			'mod_scorm',
			array(
				'type'	=> 'integer',
				'length'=> 1,
				'notnull' => false,
				'default' => 1
			)
		);
	}
	if(!$ilDB->tableColumnExists('page_layout','mod_portfolio'))
	{
		$ilDB->addTableColumn(
			'page_layout',
			'mod_portfolio',
			array(
				'type'	=> 'integer',
				'length'=> 1,
				'notnull' => false
			)
		);
	}
?>
<#3443>
<?php
$ilDB->manipulate("UPDATE style_data SET ".
	" uptodate = ".$ilDB->quote(0, "integer")
	);
?>
<#3444>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETreeControl", "rte_tree", "div",
				array());
?>
<#3445>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTETreeControlLink", "rte_tclink", "a",
				array());
?>
<#3446>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("RTEDragBar", "rte_drag", "div",
				array());
?>
<#3447>
<?php
$setting = new ilSetting();
$setting->set("enable_sahs_pd", 1);
?>
<#3448>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3449>
<?php
	if (!$ilDB->tableColumnExists("usr_portf_acl", "extended_data"))
	{
		$ilDB->addTableColumn("usr_portf_acl", "extended_data", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 200,
		 	"fixed" => false));
	}
?>
<#3450>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3451>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3452>
<?php
	// register new object type 'dtpl' for portfolio administration
	$id = $ilDB->nextId("object_data");
	$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "typ", "otpl", "Object Template administration", -1, ilUtil::now(), ilUtil::now()));
	$typ_id = $id;

	// create object data entry
	$id = $ilDB->nextId("object_data");
	$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($id, "otpl", "__ObjectTemplateAdministration", "Object Template Administration", -1, ilUtil::now(), ilUtil::now()));

	// create object reference entry
	$ref_id = $ilDB->nextId('object_reference');
	$res = $ilDB->manipulateF("INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
		array("integer", "integer"),
		array($ref_id, $id));

	// put in tree
	$tree = new ilTree(ROOT_FOLDER_ID);
	$tree->insertNode($ref_id, SYSTEM_FOLDER_ID);

	// add rbac operations
	// 1: edit_permissions, 2: visible, 3: read, 4:write
	$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
		array("integer", "integer"),
		array($typ_id, 1));
	$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
		array("integer", "integer"),
		array($typ_id, 2));
	$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
		array("integer", "integer"),
		array($typ_id, 3));
	$ilDB->manipulateF("INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)",
		array("integer", "integer"),
		array($typ_id, 4));
?>
<#3453>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3454>
<?php

	if (!$ilDB->tableColumnExists("didactic_tpl_settings", "info"))
	{
		$ilDB->addTableColumn("didactic_tpl_settings", "info", array(
			"type" => "text",
			"notnull" => false,
		 	"length" => 4000,
		 	"fixed" => false));
	}
?>

<#3455>
<?php

	$query = 'DELETE FROM didactic_tpl_objs';
	$ilDB->manipulate($query);

	if (!$ilDB->tableColumnExists("didactic_tpl_objs", "ref_id"))
	{
		$ilDB->addTableColumn("didactic_tpl_objs", "ref_id", array(
			"type" => "integer",
			"notnull" => false,
		 	"length" => 4
		));
	}

	$ilDB->dropPrimaryKey('didactic_tpl_objs');
	$ilDB->addPrimaryKey('didactic_tpl_objs',array('ref_id','tpl_id'));
?>

<#3456>
<?php
	if (!$ilDB->tableColumnExists("chatroom_settings", "display_past_msgs"))
	{
		$ilDB->addTableColumn("chatroom_settings", "display_past_msgs", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 4,
			"default" => 0));
	}
?>
<#3457>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3458>
<?php
	$ilDB->addTableColumn("usr_data", "last_visited", array(
		"type" => "clob",
		"notnull" => false,
		"default" => null)
	);
?>
<#3459>
<?php
	if (!$ilDB->tableColumnExists("chatroom_history", "sub_room"))
	{
		$ilDB->addTableColumn("chatroom_history", "sub_room", array(
			"type" => "integer",
			"notnull" => false,
			"length" => 4,
			"default" => 0));
	}
?>
<#3460>
<?php
	$ilDB->setLimit(1);
	$query = "SELECT object_data.obj_id, object_reference.ref_id FROM object_data INNER JOIN object_reference on object_data.obj_id = object_reference.obj_id WHERE type = 'chta'";
	$rset = $ilDB->query( $query );
	$row = $ilDB->fetchAssoc($rset);
	
	$chatfolder_obj_id = $row['obj_id'];
	$chatfolder_ref_id = $row['ref_id'];
	
	$settings = new ilSetting('chatroom');
	$public_room_ref_id = $settings->get('public_room_ref', 0);
	
	$query = "SELECT object_data.obj_id, object_reference.ref_id FROM object_data INNER JOIN object_reference on object_data.obj_id = object_reference.obj_id WHERE ref_id = %s AND deleted IS null";
	$rset = $ilDB->queryF($query, array('integer'), array($public_room_ref_id));
	
	if (!$public_room_ref_id || !$rset->numRows($res)) {

	    // add chat below ChatSettings for personal desktop chat

	    $obj_id = $ilDB->nextId('object_data');

	    // Get chat settings id

	    $ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
			    "VALUES (%s, %s, %s, %s, %s, %s, %s)",
			    array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
			    array($obj_id, "chtr", "Public Chatroom", "Public Chatroom", -1, ilUtil::now(), ilUtil::now()));

	    $ref_id = $ilDB->nextId('object_reference');

	    // Create reference
	    $ilDB->manipulateF(
		    "INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
		    array('integer', 'integer'),
		    array($ref_id,$obj_id)
	    );

	    // put in tree
	    $tree = new ilTree(ROOT_FOLDER_ID);
	    $tree->insertNode($ref_id,$chatfolder_ref_id);

	    $rolf_obj_id = $ilDB->nextId('object_data');

	    // Create role folder
	    $ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
			    "VALUES (%s, %s, %s, %s, %s, %s, %s)",
			    array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
			    array($rolf_obj_id, "rolf", $obj_id, "(ref_id ".$chat_ref_id.")", -1, ilUtil::now(), ilUtil::now()));

	    $rolf_ref_id = $ilDB->nextId('object_reference');

	    // Create reference
	    $ilDB->manipulateF(
		    "INSERT INTO object_reference (ref_id, obj_id) VALUES (%s, %s)",
		    array('integer', 'integer'),
		    array($rolf_ref_id,$rolf_obj_id)
	    );

	    // put in tree
	    $tree->insertNode($rolf_ref_id,$ref_id);

	    $role_obj_id = $ilDB->nextId('object_data');

	    // Create role
	    $ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
			    "VALUES (%s, %s, %s, %s, %s, %s, %s)",
			    array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
			    array($role_obj_id, "role", "il_chat_moderator_".$ref_id, "Moderator of chat obj_no.".$obj_id, -1, ilUtil::now(), ilUtil::now()));

	    // Insert role_data
	    $ilDB->manipulateF(
		    'INSERT INTO role_data (role_id) VALUES (%s)',
		    array('integer'),
		    array($role_obj_id)
	    );

	    $permissions = ilRbacReview::_getOperationIdsByName(array('visible','read','moderate'));
	    $rbacadmin = new ilRbacAdmin();
	    $rbacadmin->grantPermission($role_obj_id, $permissions,$ref_id);
	    $rbacadmin->assignRoleToFolder($role_obj_id,$rolf_ref_id);

	    
	    $id = $ilDB->nextId('chatroom_settings');
	    $ilDB->insert(
		'chatroom_settings',
		array(
		    'room_id' => array('integer', $id),
		    'object_id' => array('integer', $obj_id),
		    'room_type' => array('text', 'default'),
		    'allow_anonymous' => array('integer', 0),
		    'allow_custom_usernames' => array('integer', 0),
		    'enable_history' => array('integer', 0),
		    'restrict_history' => array('integer', 0),
		    'autogen_usernames' => array('text', 'Anonymous #'),
		    'allow_private_rooms' => array('integer', 1),
		)
	    );

	    $settings = new ilSetting('chatroom');
	    $settings->set('public_room_ref', $ref_id);
	}
 
?>
<#3461>
<?php

	$chat_modetator_tpl_id = $ilDB->nextId('object_data');
			
	$ilDB->manipulateF("
		INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
		"VALUES (%s, %s, %s, %s, %s, %s, %s)",
		array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
		array($chat_modetator_tpl_id, "rolt", "il_chat_moderator", "Moderator template for chat moderators", -1, ilUtil::now(), ilUtil::now()));

	$query = 'SELECT ops_id FROM rbac_operations WHERE operation = ' . $ilDB->quote('moderate', 'text');
	$rset = $ilDB->query($query);
	$row = $ilDB->fetchAssoc($rset);
	$moderateId = $row['ops_id'];

	$chat_modetator_ops = array(2,3,4,$moderateId);
	foreach ($chat_modetator_ops as $op_id)
	{
		$query = "INSERT INTO rbac_templates
		VALUES (".$ilDB->quote($chat_modetator_tpl_id).", 'chtr', ".$ilDB->quote($op_id).", 8)";
		$ilDB->manipulate($query);
	}

	$query = "INSERT
		INTO rbac_fa
		VALUES (".$ilDB->quote($chat_modetator_tpl_id).", 8, 'n', 'n')";
	$ilDB->manipulate($query);
?>
<#3462>
<?php

	$ilDB->setLimit(1);
	$query = "SELECT object_data.obj_id, object_reference.ref_id FROM object_data INNER JOIN object_reference on object_data.obj_id = object_reference.obj_id WHERE type = 'chta'";
	$rset = $ilDB->query( $query );
	$row = $ilDB->fetchAssoc($rset);
	
	$chatfolder_obj_id = $row['obj_id'];
	$chatfolder_ref_id = $row['ref_id'];
	
	$settings = new ilSetting('chatroom');
	$public_room_ref_id = $settings->get('public_room_ref', 0);

	if ($public_room_ref_id) {
		$tree = new ilTree(ROOT_FOLDER_ID);
		$pid = $tree->getParentId($public_room_ref_id);
		if ($pid != $chatfolder_ref_id) {
			$tree->moveTree($public_room_ref_id, $chatfolder_ref_id);
		}
	}
?>
<#3463>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3464>
<?php
if(!$ilDB->tableExists('note_settings'))
{
	$fields = array (
		'rep_obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0),

		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0),

	 	'obj_type' => array(
			'type' => 'text',
			'notnull' => true,
			'length' => 10,
			'default' => "-"),
		
	 	'activated' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0)
	);
	$ilDB->createTable('note_settings', $fields);
	$ilDB->addPrimaryKey('note_settings', array('rep_obj_id', 'obj_id', 'obj_type'));
}
?>
<#3465>
<?php
	$set = $ilDB->query("SELECT * FROM settings ".
		" WHERE module = ".$ilDB->quote("notes", "text")
		);
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$kw_arr = explode("_", $rec["keyword"]);
		if ($rec["value"] == "1" && $kw_arr[0] == "activate")
		{
			if ($kw_arr[3] == "")
			{
				$kw_arr[3] = "-";
			}
			$q = "INSERT INTO note_settings ".
				"(rep_obj_id, obj_id, obj_type, activated) VALUES (".
				$ilDB->quote((int) $kw_arr[1], "integer").",".
				$ilDB->quote((int) $kw_arr[2], "integer").",".
				$ilDB->quote($kw_arr[3], "text").",".
				$ilDB->quote(1, "integer").
				")";
			$ilDB->manipulate($q);
		}
	}
?>
<#3466>
<?php
	if (!$ilDB->tableColumnExists("chatroom_settings", "private_rooms_enabled"))
	{
		$ilDB->addTableColumn("chatroom_settings", "private_rooms_enabled", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 4,
			"default" => 0));
	}
?>
<#3467>
<?php
	$settings = new ilSetting('chatroom');
	$public_room_ref_id = $settings->get('public_room_ref', 0);
	if ($public_room_ref_id) {
		//$ilDB->manipulateF('UPDATE ')
		$rset = $ilDB->queryF('SELECT obj_id FROM object_reference WHERE ref_id = %s', array('integer'), array($public_room_ref_id));
		$row = $ilDB->fetchAssoc($rset);
		if ($row) {
			$obj_id = $row['obj_id'];
			$ilDB->manipulateF('UPDATE chatroom_settings SET private_rooms_enabled = 1 WHERE object_id = %s', array('integer'), array($obj_id));
		}
	}
?>
<#3468>
<?php
	$statement = $ilDB->queryF('
		SELECT obj_id FROM object_data 
		WHERE type = %s 
		AND title = %s',
		array('text', 'text'),
		array('rolt', 'il_chat_moderator'));

	$res = $ilDB->fetchAssoc($statement);

	if (!$res || $res->obj_id) {
		$chat_modetator_tpl_id = $ilDB->nextId('object_data');
		
		$ilDB->manipulateF("INSERT INTO object_data (obj_id, type, title, description, owner, create_date, last_update) ".
			"VALUES (%s, %s, %s, %s, %s, %s, %s)",
			array("integer", "text", "text", "text", "integer", "timestamp", "timestamp"),
			array($chat_modetator_tpl_id, "rolt", "il_chat_moderator", "Moderator template for chat moderators", -1, ilUtil::now(), ilUtil::now()));

		$query = 'SELECT ops_id FROM rbac_operations WHERE operation = ' . $ilDB->quote('moderate', 'text');
		$rset = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($rset);
		$moderateId = $row['ops_id'];

		$chat_modetator_ops = array(2,3,4,$moderateId);
		foreach ($chat_modetator_ops as $op_id)
		{
			$query = "INSERT INTO rbac_templates
			VALUES (".$ilDB->quote($chat_modetator_tpl_id).", 'chtr', ".$ilDB->quote($op_id).", 8)";
			$ilDB->manipulate($query);
		}

		$query = "INSERT
			INTO rbac_fa
			VALUES (".$ilDB->quote($chat_modetator_tpl_id).", 8, 'n', 'n')";
		$ilDB->manipulate($query);
	}
?>
<#3469>
<?php
	$query = 'SELECT ops_id FROM rbac_operations WHERE operation = ' . $ilDB->quote('moderate', 'text');
	$rset = $ilDB->query($query);
	$row = $ilDB->fetchAssoc($rset);
	$moderateId = $row['ops_id'];

	$statement = $ilDB->queryF('
		SELECT obj_id FROM object_data 
		WHERE type = %s 
		AND title = %s',
		array('text', 'text'),
		array('rolt', 'il_chat_moderator'));

	$res = $ilDB->fetchAssoc($statement);

	$typ_id = $res['obj_id'];
	/*
	$chat_moderator_ops = array(2,3,4,$moderateId);
	foreach($chat_moderator_ops as $new_ops_id) {
		$query = $ilDB->manipulateF(
			'INSERT INTO rbac_ta (typ_id, ops_id) VALUES (%s, %s)',
			array('integer','integer'),
			array($typ_id, $new_ops_id)
		);
	}
	 */
?>
<#3470>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3471>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3472>
<?php
	$statement = $ilDB->queryF('
		SELECT obj_id FROM object_data 
		WHERE type = %s 
		AND title = %s',
		array('text', 'text'),
		array('rolt', 'il_chat_moderator'));

	$res = $ilDB->fetchAssoc($statement);

	if ($res && $res['obj_id']) {
		$chat_modetator_tpl_id = $res['obj_id'];
		
		$ilDB->manipulateF("DELETE FROM rbac_ta WHERE typ_id = %s",
			array("integer"),
			array($chat_modetator_tpl_id)
		);
	}
?>
<#3473>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3474>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3475>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3476>
<?php
	if (!$ilDB->tableColumnExists("exc_data", "certificate_visibility"))
	{
		$ilDB->addTableColumn("exc_data", "certificate_visibility", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 1,
			"default" => 0));
	}
?>
<#3477>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3478>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3479>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','sequencing'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'sequencing',
			array(
				"type" => "text",
				'length' => 1,
				"notnull" => true,
				"default" => 'y'
			)
		);
	$ilDB->query("UPDATE sahs_lm SET sequencing = 'y'");
}
?>
<#3480>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','interactions'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'interactions',
			array(
				"type" => "text",
				'length' => 1,
				"notnull" => true,
				"default" => 'y'
			)
		);
		$ilDB->query("UPDATE sahs_lm SET interactions = 'y'");
	}
?>
<#3481>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','objectives'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'objectives',
			array(
				"type" => "text",
				'length' => 1,
				"notnull" => true,
				"default" => 'y'
			)
		);
		$ilDB->query("UPDATE sahs_lm SET objectives = 'y'");
	}
?>
<#3482>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','time_from_lms'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'time_from_lms',
			array(
				"type" => "text",
				'length' => 1,
				"notnull" => true,
				"default" => 'n'
			)
		);
		$ilDB->query("UPDATE sahs_lm SET time_from_lms = 'n'");
	}
?>
<#3483>
<?php
	if(!$ilDB->tableColumnExists('sahs_lm','comments'))
	{
		$ilDB->addTableColumn(
			'sahs_lm',
			'comments',
			array(
				"type" => "text",
				'length' => 1,
				"notnull" => true,
				"default" => 'y'
			)
		);
		$ilDB->query("UPDATE sahs_lm SET comments = 'y'");
	}
?>
<#3484>
<?php

	// Get all users with extended profile pages
	$set = $ilDB->query("SELECT DISTINCT(user_id) FROM usr_ext_profile_page");
	$user_ids = array();
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$user_ids[] = $rec["user_id"];
	}

	if(sizeof($user_ids))
	{
		foreach($user_ids as $user_id)
		{
			$portfolio_id = $ilDB->nextId("object_data");
			
			// create portfolio object			
			$ilDB->manipulate("INSERT INTO object_data".
				 " (obj_id,type,title,description,owner,create_date,last_update,import_id)".
				 " VALUES (".
				 $ilDB->quote($portfolio_id, "integer").",".
				 $ilDB->quote("prtf", "text").",".
				 $ilDB->quote("Default", "text").",".
				 $ilDB->quote("", "text").",".
				 $ilDB->quote($user_id, "integer").",".
				 $ilDB->now().",".
				 $ilDB->now().",".
				 $ilDB->quote("", "text").")");

			// create portfolio data
			$ilDB->manipulate("INSERT INTO usr_portfolio (id,is_online,is_default)".
				" VALUES (".$ilDB->quote($portfolio_id, "integer").",".
				$ilDB->quote(true, "integer").",".
				$ilDB->quote(true, "integer").")");
			
			$page_id = $ilDB->nextId("usr_portfolio_page");

			// create first page as profile			
			$fields = array("portfolio_id" => array("integer", $portfolio_id),
				"type" => array("integer", 1),
				"title" => array("text", "###-"),
				"order_nr" => array("integer", 10),
				"id" => array("integer", $page_id));
			$ilDB->insert("usr_portfolio_page", $fields);

			// first page has public profile as default
			$xml = "<PageObject>".
				"<PageContent PCID=\"".ilUtil::randomHash()."\">".
					"<Profile Mode=\"inherit\" User=\"".$user_id."\"/>".
				"</PageContent>".
			"</PageObject>";

			// create first page core
			$ilDB->insert("page_object", array(
				"page_id" => array("integer", $page_id),
				"parent_id" => array("integer", $portfolio_id),
				"content" => array("clob", $xml),
				"parent_type" => array("text", "prtf"),
				"create_user" => array("integer", $user_id),
				"last_change_user" => array("integer", $user_id),
				"inactive_elements" => array("integer", 0),
				"int_links" => array("integer", 0),
				"created" => array("timestamp", ilUtil::now()),
				"last_change" => array("timestamp", ilUtil::now())
				));

			// migrate extended profile pages
			$set = $ilDB->query("SELECT ext.id, ext.title, pg.content FROM usr_ext_profile_page ext".
				" JOIN page_object pg ON (pg.page_id = ext.id)".
				" WHERE ext.user_id = ".$ilDB->quote($user_id, "integer").
				" AND parent_type =".$ilDB->quote("user", "text").
				" AND parent_id = ".$ilDB->quote($user_id, "integer").
				" ORDER BY ext.order_nr");
			$order = 10;
			while ($rec = $ilDB->fetchAssoc($set))
			{
				$order += 10;
				
				$page_id = $ilDB->nextId("usr_portfolio_page");
				
				// #8600: title may be null
				$page_title = $rec["title"];
				if(!$page_title)
				{
					$page_title = "prtf".$portfolio_id."_".$rec["id"];					
				}					
				
				// create portfolio page				
				$fields = array("portfolio_id" => array("integer", $portfolio_id),
					"type" => array("integer", 1),
					"title" => array("text", $page_title),
					"order_nr" => array("integer", $order),
					"id" => array("integer", $page_id));
				$ilDB->insert("usr_portfolio_page", $fields);
				
				// create page core
				$ilDB->insert("page_object", array(
					"page_id" => array("integer", $page_id),
					"parent_id" => array("integer", $portfolio_id),
					"content" => array("clob", $rec["content"]),
					"parent_type" => array("text", "prtf"),
					"create_user" => array("integer", $user_id),
					"last_change_user" => array("integer", $user_id),
					"inactive_elements" => array("integer", 0),
					"int_links" => array("integer", 0),
					"created" => array("timestamp", ilUtil::now()),
					"last_change" => array("timestamp", ilUtil::now())
					));		
			}
		}
	}
?>
<#3485>
<?php
	$ilDB->addPrimaryKey('adm_settings_template', array('id'));
?>
<#3486>
<?php
	$ilDB->addPrimaryKey('ecs_server', array('server_id'));
?>
<#3487>
<?php
	$setting = new ilSetting();
	$setting->set("obj_dis_creation_dbk", 1);
?>
<#3488>
<?php

	$ilDB->modifyTableColumn('frm_settings', 'preset_subject',
	array('type' => 'integer',
		'notnull' => true,
		'length' => 1,
		'default' => 1));	
?>
<#3489>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3490>
<?php
	$ilDB->addIndex("page_object", array("parent_id"), "i2");
?>
<#3491>
<?php
	$ilDB->addIndex("page_history", array("page_id"), "i1");
?>
<#3492>
<?php
	$ilDB->addIndex('cmi_interaction', array('cmi_node_id'), "i4");
?>
<#3493>
<?php
	$ilDB->addIndex('object_data', array('import_id'), "i4");
?>
<#3494>
<?php
	$ilDB->addIndex('usr_data', array('ext_account', 'auth_mode'), "i2");
?>
<#3495>
<?php
	$ilDB->addIndex('event', array('obj_id'), "i1");
?>
<#3496>
<?php
	$ilDB->addIndex('conditions', array('target_obj_id'), "i1");
?>
<#3497>
<?php
	$ilDB->addIndex('mob_usage', array('usage_id'), "i1");
?>
<#3498>
<?php
	$ilDB->addIndex('lo_access', array('usr_id'), "i1");
?>
<#3499>
<?php
	$ilDB->addIndex('cal_categories', array('type'), "i3");
?>
<#3500>
<?php
	$ilDB->addIndex('tree', array('lft'), "i4");
?>
<#3501>
<?php
	$ilDB->addTableColumn("skl_tree_node", "draft", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
?>
<#3502>
<?php
	$next_id = $ilDB->nextId('rbac_operations');
	
	$ilDB->insert("rbac_operations", array(
		"ops_id" => array("integer", $next_id),
		"operation" => array("text", "create_chtr"),
		"description" => array("text", "create chatroom"),
		"class" => array("text", "create"),
		"op_order" => array("integer", 9999)
		));
?>
<#3503>
<?php
	if(!$ilDB->tableColumnExists('il_object_def', 'administration'))
	{
		$ilDB->addTableColumn(
			"il_object_def",
			"administration",
			array(
				"type" => "integer",
				"length" => 1,
				"notnull" => true,
				"default" => 0)
		);
	}
?>
<#3504>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3505>
<?php
	// activate pool usage
	$ilDB->manipulate("UPDATE svy_svy SET pool_usage = ".$ilDB->quote(1, "integer"));	
?>
<#3506>
<?php
	$setting = new ilSetting();
	$ilrqtix = $setting->get("ilrqtix");
	if (!$ilrqtix)
	{
		$ilDB->addIndex("il_request_token", array("stamp"), "i4");
		$setting->set("ilrqtix", 1);
	}
?>
<#3507>
<?php
	$setting = new ilSetting();
	$ilmpathix = $setting->get("ilmpathix");
	if (!$ilmpathix)
	{
		$ilDB->addIndex("mail_attachment", array("path"), "i1");
		$setting->set("ilmpathix", 1);
	}
?>
<#3508>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3509>
<?php
	if(!$ilDB->tableColumnExists('tst_tests', 'print_bs_with_res'))
	{
		$ilDB->addTableColumn('tst_tests', 'print_bs_with_res', array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true,
				'default' => 1
			)
		);
	}
?>
<#3510>
<?php
	$setting = new ilSetting();
  	$allreadyApplied = $setting->get('ilGlobalTstPoolUsageSettingInitilisation', 0);
	
  	if( !$allreadyApplied )
	{
		$ilDB->queryF("UPDATE tst_tests SET pool_usage = %s", array('integer'), array(1));
		
		$setting->set("ilGlobalTstPoolUsageSettingInitilisation", 1);
	}
?>
<#3511>
<?php	
	if(!$ilDB->tableExists('obj_lp_stat'))
	{	
		$fields = array(
			'type' => array(
				'type' => 'text',
				'length' => 4,
				'notnull' => true
				),
			'obj_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'yyyy' => array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => true
				),
			'mm' => array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true
				),
			'dd' => array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true
				),
			'fulldate' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'mem_cnt' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'in_progress' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'completed' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'failed' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'not_attempted' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				)
		);
		$ilDB->createTable('obj_lp_stat', $fields);
		$ilDB->addPrimaryKey('obj_lp_stat', array("obj_id", "fulldate"));
	}
?>
<#3512>
<?php	
	if(!$ilDB->tableExists('obj_type_stat'))
	{
		$fields = array(
			'type' => array(
				'type' => 'text',
				'length' => 4,
				'notnull' => true
				),				
			'yyyy' => array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => true
				),
			'mm' => array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true
				),
			'dd' => array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true
				),
			'fulldate' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'cnt_references' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'cnt_objects' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'cnt_deleted' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				)
		);
		$ilDB->createTable('obj_type_stat', $fields);
		$ilDB->addPrimaryKey('obj_type_stat', array("type", "fulldate"));
	}
?>
<#3513>
<?php	
	if(!$ilDB->tableExists('obj_user_stat'))
	{
		$fields = array(	
			'obj_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'yyyy' => array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => true
				),
			'mm' => array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true
				),
			'dd' => array(
				'type' => 'integer',
				'length' => 1,
				'notnull' => true
				),
			'fulldate' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'counter' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				)
		);
		$ilDB->createTable('obj_user_stat', $fields);
		$ilDB->addPrimaryKey('obj_user_stat', array("obj_id", "fulldate"));
	}
?>
<#3514>
<?php
	if(!$ilDB->tableColumnExists("obj_stat", "tstamp"))
	{
		// table must be empty to add not null column
		$ilDB->manipulate("DELETE FROM obj_stat");
			
		$ilDB->addTableColumn('obj_stat', 'tstamp', array(
			"type" => "integer",
			"notnull" => true,
			"length" => 4));
			
		$ilDB->addIndex("obj_stat", array("tstamp", "obj_id"), "i2");
	}
?>
<#3515>
<?php	
	if(!$ilDB->tableExists('usr_session_stats_raw'))
	{
		$fields = array(	
			'session_id' => array(
				'type' => 'text',
				'length' => 80,
				'notnull' => true
				),
			'type' => array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => true
				),
			'start_time' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'end_time' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'end_context' => array(
				'type' => 'integer',
				'length' => 2,
				'notnull' => false
				),
			'user_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				)
		);
		$ilDB->createTable('usr_session_stats_raw', $fields);
		$ilDB->addPrimaryKey('usr_session_stats_raw', array('session_id'));
	}
?>
<#3516>
<?php	
	if(!$ilDB->tableExists('usr_session_stats'))
	{
		$fields = array(	
			'slot_begin' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'slot_end' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'active_min' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'active_max' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'active_avg' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'active_end' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'opened' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'closed_manual' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'closed_expire' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'closed_idle' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'closed_idle_first' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'closed_limit' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'closed_login' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				),
			'max_sessions' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => false
				)						
		);
		$ilDB->createTable('usr_session_stats', $fields);
		$ilDB->addIndex('usr_session_stats', array('slot_end'), 'i1');
	}
?>
<#3517>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3518>
<?php
	// taxonomy tree
	$fields = array(
			'tax_tree_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'child' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'parent' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => false,
					'default' => null
			),
			'lft' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'rgt' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'depth' => array(
					'type' => 'integer',
					'length' => 2,
					'notnull' => true,
					'default' => 0
			)
	);
	$ilDB->createTable('tax_tree', $fields);
?>
<#3519>
<?php
	// taxonomy node
	$fields = array(
			'obj_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'title' => array(
					'type' => 'text',
					'length' => 200,
					'notnull' => false
			),
			'type' => array(
					'type' => 'text',
					'length' => 4,
					'fixed' => true,
					'notnull' => false
			),
			'create_date' => array(
					'type' => 'timestamp',
					'notnull' => false
			),
			'last_update' => array(
					'type' => 'timestamp',
					'notnull' => false
			)
	);
	$ilDB->createTable('tax_node', $fields);
	$ilDB->createSequence('tax_node');
	$ilDB->addPrimaryKey("tax_node", array("obj_id"));
?>
<#3520>
<?php
	// taxonomy use
	$fields = array(
		'tax_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		),
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true,
			'default' => 0
		));
	$ilDB->createTable('tax_usage', $fields);
	$ilDB->addPrimaryKey("tax_usage", array("tax_id", "obj_id"));
?>
<#3521>
<?php
	if(!$ilDB->tableColumnExists("crs_settings", "status_dt"))
    {		
		$ilDB->addTableColumn("crs_settings", "status_dt", array(
                'type'     => 'integer',
                'length'   => 1,
                'default'  => 2));		
    }	
	if(!$ilDB->tableColumnExists("crs_members", "origin"))
    {		
		$ilDB->addTableColumn("crs_members", "origin", array(
                'type'     => 'integer',
                'length'   => 4,
                'default'  => 0));		
    }	
	if(!$ilDB->tableColumnExists("crs_members", "origin_ts"))
    {		
		$ilDB->addTableColumn("crs_members", "origin_ts", array(
                'type'     => 'integer',
                'length'   => 4,
                'default'  => 0));		
    }
?>
<#3522>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3523>
<?php
	if(!$ilDB->tableExists('il_event_handling'))
	{
		$fields = array(
			'component' => array(
				'type' => 'text',
				'length' => 50,
				'notnull' => true,
				'fixed' => false
			),
			'type' => array(
				'type' => 'text',
				'length' => 10,
				'notnull' => true,
				'fixed' => false
			),
			'id' => array(
				'type' => 'text',
				'length' => 100,
				'notnull' => true,
				'fixed' => false
			));
		$ilDB->createTable('il_event_handling', $fields);
	}
?>
<#3524>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3525>
<?php
	if(!$ilDB->tableColumnExists('mail_template', 'att_file'))
	{
		$atts = array(
			'type'		=> 'text',
			'length'	=> 400,
			'default'	=> '',
			'notnull'	=> false
		);
		$ilDB->addTableColumn('mail_template', 'att_file', $atts);
	}
?>
<#3526>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3527>
<?php
$atts = array(
	'type'		=> 'integer',
	'length'	=> 4,
	'default'	=> 0,
	'notnull'	=> true
);
$ilDB->addTableColumn('tax_node', 'tax_id', $atts);
?>
<#3528>
<?php


$ilDB->manipulate("INSERT INTO tax_node ".
	"(obj_id, title, type, create_date, last_update, tax_id) VALUES (".
	$ilDB->quote(1, "integer").",".
	$ilDB->quote("Dummy top node for all tax trees.", "text").",".
	$ilDB->quote("", "text").",".
	$ilDB->now().",".
	$ilDB->now().",".
	$ilDB->quote(0, "integer").
	")");

?>
<#3529>
<?php
$ilDB->addTableColumn("tax_node", "order_nr", array(
		'type'	=> 'integer',
		'length'=> 4,
		'default'=> 0,
		'notnull' => true
	));
?>
<#3530>
<?php
	include_once "./Services/Object/classes/class.ilObject.php";
	include_once "Services/Administration/classes/class.ilSetting.php";	
	$ilSetting = new ilSetting();
	$ilSetting->set("rep_shorten_description", 1);
	$ilSetting->set("rep_shorten_description_length", ilObject::TITLE_LENGTH);
?>
<#3531>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3532>
<?php
$fields = array(
	'chap' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'component' => array(
		'type' => 'text',
		'length' => 10,
		'notnull' => true,
		'fixed' => false
	),
	'screen_id' => array(
		'type' => 'text',
		'length' => 30,
		'notnull' => true,
		'fixed' => false
	),
	'screen_sub_id' => array(
		'type' => 'text',
		'length' => 30,
		'notnull' => true,
		'fixed' => false
	)
	);
$ilDB->createTable('help_map', $fields);
$ilDB->addPrimaryKey('help_map', array('component', 'screen_id', 'screen_sub_id', 'chap'));
$ilDB->addIndex("help_map", array("screen_id"), "sc");
$ilDB->addIndex("help_map", array("chap"), "ch");
?>
<#3533>
<?php	
	if(!$ilDB->tableExists('usr_session_log'))
	{
		$fields = array(	
			'tstamp' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),
			'maxval' => array(
				'type' => 'integer',
				'length' => 3,
				'notnull' => true
				),
			'user_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
				),			
		);
		$ilDB->createTable('usr_session_log', $fields);
	}
?>
<#3534>
<?php
	$setting = new ilSetting();
	$ilpghi2 = $setting->get("ilpghi2");
	if (!$ilpghi2)
	{
		$ilDB->addIndex("page_history", array("parent_id", "parent_type", "hdate"), "i2");
		$setting->set("ilpghi2", 1);
	}
?>
<#3535>
<?php
	$setting = new ilSetting();
	$ilpgi3 = $setting->get("ilpgi3");
	if (!$ilpgi3)
	{
		$ilDB->addIndex("page_object", array("parent_id", "parent_type", "last_change"), "i3");
		$setting->set("ilpgi3", 1);
	}
?>
<#3536>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3537>
<?php	
	// if rpc-server is enabled, enable certificates (migrating old behaviour)
	$ilSetting = new ilSetting();
	if($ilSetting->get('rpc_server_host') && $ilSetting->get('rpc_server_port'))
	{
		$certificate_active = new ilSetting("certificate");
		$certificate_active->set("active", true);
	}
?>
<#3538>
<?php	
	if (!$ilDB->tableColumnExists("udf_definition", "certificate"))
	{
		$ilDB->addTableColumn("udf_definition", "certificate", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 1,
			"default" => 0));
	}
?>
<#3539>
<?php
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'tt_text' => array(
		'type' => 'text',
		'length' => 4000,
		'notnull' => false,
		'fixed' => false
	)
	);
$ilDB->createTable('help_tooltip', $fields);
$ilDB->addPrimaryKey('help_tooltip', array('id'));
$ilDB->createSequence("help_tooltip");
?>
<#3540>
<?php
$fields = array(
	'text_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'tt_id' => array(
		'type' => 'text',
		'length' => 30,
		'notnull' => true,
		'fixed' => false
	)
	);
$ilDB->createTable('help_tt_map', $fields);
$ilDB->addPrimaryKey('help_tt_map', array('text_id', 'tt_id'));
?>
<#3541>
<?php

	if( !$ilDB->tableExists('qpl_hints') )
	{
		$ilDB->createTable('qpl_hints', array(
			'qht_hint_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'qht_question_fi' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'qht_hint_index' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'qht_hint_points' => array(
				'type' => 'float',
				'notnull' => true,
				'default' => 0
			),
			'qht_hint_text' => array(
				'type' => 'text',
				'length' => 4000,
				'fixed' => false,
				'notnull' => true
			)
		));
		
		$ilDB->addPrimaryKey('qpl_hints', array('qht_hint_id'));
		$ilDB->createSequence('qpl_hints');
	}
	
?>
<#3542>
<?php
	if(!$ilDB->tableColumnExists("usr_session_stats", "closed_misc"))
    {		
		$ilDB->addTableColumn("usr_session_stats", "closed_misc", array(
                'type'     => 'integer',
                'length'   => 4,
                'default'  => 0));		
    }	
?>
<#3543>
<?php

	$setting = new ilSetting();
	$ilchtrbacfix = $setting->get("ilchtrbacfix");
	if(!$ilchtrbacfix)
	{
		$result = $ilDB->query(
			'SELECT ops_id 
			FROM rbac_operations 
			WHERE operation = '. $ilDB->quote('create_chat', 'text')
		);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$chat_id = $row['ops_id'];
		}

		$result = $ilDB->query(
			'SELECT ops_id
			FROM rbac_operations
			WHERE operation = ' . $ilDB->quote('create_chtr', 'text')
		);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$chatroom_id = $row['ops_id'];
		}

		if ($chat_id && $chatroom_id)
		{
			$result = $ilDB->query(
				'SELECT * 
				FROM rbac_pa
				WHERE ' . $ilDB->like('ops_id', 'text', '%i:' . $chat_id . ';%')
			);

			$statement = $ilDB->prepareManip(
				'UPDATE rbac_pa 
				SET ops_id = ?
				WHERE rol_id = ?
				AND ref_id = ?',
				array('text', 'integer', 'integer')
			);

			$rows = array();
			while ($row = $ilDB->fetchAssoc($result))
			{
				$rows[] = $row;
			}

			foreach ($rows as $row)
			{
				$ops_arr = unserialize($row['ops_id']);

				if(!$ops_arr)
				{
					continue;
				}

				$key = array_search($chat_id, $ops_arr);
				if(!$key)
				{
					continue;
				}

				$ops_arr[$key] = $chatroom_id;
				$new_ops = serialize($ops_arr);
				$ilDB->execute(
					$statement, 
					array($new_ops, $row['rol_id'], $row['ref_id'])
				);
			}

			$like =  '%s:' . strlen($chat_id) .':"'. $chat_id . '";%';
			$result = $ilDB->query(
				'SELECT * FROM rbac_pa
				WHERE ' . $ilDB->like('ops_id', 'text', $like)
			);

			$rows = array();
			while ($row = $ilDB->fetchAssoc($result))
			{
				$rows[] = $row;
			}

			foreach ($rows as $row)
			{
				$ops_arr = unserialize($row['ops_id']);	
				if(!$ops_arr)
				{
					continue;
				}

				$key = array_search($chat_id, $ops_arr);
				if(!$key)
				{
					continue;
				}

				$ops_arr[$key] = $chatroom_id;
				$new_ops = serialize($ops_arr);
				$ilDB->execute(
					$statement, 
					array($new_ops, $row['rol_id'], $row['ref_id'])
				);
			}
			$ilDB->free($statement);

			$ilDB->manipulate(
				'DELETE
				FROM rbac_operations
				WHERE ops_id = ' . $ilDB->quote($chat_id, 'integer')
			);
			
			$ilDB->manipulate(
				'UPDATE rbac_ta 
				SET ops_id = ' . $ilDB->quote($chatroom_id, 'integer') .'
				WHERE ops_id = ' . $ilDB->quote($chat_id, 'integer')
			);

			$ilDB->manipulate(
				'UPDATE rbac_templates 
				SET ops_id = ' . $ilDB->quote($chatroom_id, 'integer') .'
				WHERE ops_id = ' . $ilDB->quote($chat_id, 'integer')
			);
		}
		
		$setting->set("ilchtrbacfix", 1);
	}
?>
<#3544>
<?php	
	$ilDB->manipulate(
		'UPDATE rbac_templates 
		SET type = ' . $ilDB->quote('chtr', 'text') . '
		WHERE type = ' . $ilDB->quote('chat', 'text')
	);
?>
<#3545>
<?php
	if(!$ilDB->tableColumnExists("il_blog", "rss_active"))
    {		
		$ilDB->addTableColumn("il_blog", "rss_active", array(
                'type'     => 'integer',
                'length'   => 1,
                'default'  => 0));		
    }	
?>
<#3546>
<?php
	require_once 'Modules/Chatroom/classes/class.ilChatroom.php';
	require_once 'Modules/Chatroom/classes/class.ilChatroomInstaller.php';
	ilChatroomInstaller::createMissinRoomSettingsForConvertedObjects();
?>
<#3547>
<?php
	if(!$ilDB->tableColumnExists("il_media_cast_data", "sortmode"))
    {		
		$ilDB->addTableColumn("il_media_cast_data", "sortmode", array(
                'type'     => 'integer',
                'length'   => 1,
                'default'  => 3));		
    }	
?>
<#3548>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3549>
<?php

	if( !$ilDB->tableExists('il_media_cast_data_ord') )
	{
		$ilDB->createTable('il_media_cast_data_ord', array(
			'obj_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'item_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'pos' => array(
				'type' => 'integer',
				'length' => 3,
				'notnull' => true
			)
		));
		
		$ilDB->addPrimaryKey('il_media_cast_data_ord', array('obj_id','item_id'));
	}
	
?>
<#3550>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3551>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3552>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3553>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#3554>
<?php
$ilDB->addTableColumn("help_tooltip", "tt_id", array(
		'type' => 'text',
		'length' => 200,
		'notnull' => true,
		'fixed' => false
	));
?>
<#3555>
<?php
$ilDB->dropTable('help_tt_map');
?>
<#3556>
<?php

	if( !$ilDB->tableExists('usr_form_settings') )
	{
		$ilDB->dropTable('member_usr_settings');
		
		$ilDB->createTable('usr_form_settings', array(
			'user_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'id' => array(
				'type' => 'text',
				'length' => 50,
				'notnull' => true
			),
			'settings' => array(
				'type' => 'text',
				'length' => 4000,
				'notnull' => true
			)
		));
		
		$ilDB->addPrimaryKey('usr_form_settings', array('user_id','id'));
	}
	
?>
<#3557>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#3558>
<?php
$ilDB->addTableColumn("help_tooltip", "comp", array(
		'type' => 'text',
		'length' => 10,
		'notnull' => true,
		'fixed' => false
	));
?>
<#3559>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#3560>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$blog_type_id = ilDBUpdateNewObjectType::addNewType('blog', 'Blog Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE,
	ilDBUpdateNewObjectType::RBAC_OP_COPY	
);
ilDBUpdateNewObjectType::addRBACOperations($blog_type_id, $rbac_ops);

$parent_types = array('root', 'cat', 'crs', 'fold', 'grp');
ilDBUpdateNewObjectType::addRBACCreate('create_blog', 'Create Blog', $parent_types);

?>
<#3561>
<?php
$ilDB->addTableColumn("help_map", "perm", array(
		'type' => 'text',
		'length' => 20,
		'notnull' => true,
		'fixed' => false
	));
?>
<#3562>
<?php
$ilDB->dropPrimaryKey("help_map");
$ilDB->addPrimaryKey("help_map",
	array("chap", "component", "screen_id", "screen_sub_id", "perm"));
?>
<#3563>
<?php
	if(!$ilDB->tableColumnExists("il_blog_posting", "author"))
    {		
		$ilDB->addTableColumn("il_blog_posting", "author", array(
                'type'     => 'integer',
                'length'   => 4,
				'notnull'  => true,
                'default'  => 0));		
    }	
?>
<#3564>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
$type_id = ilDBUpdateNewObjectType::getObjectTypeId('book');
ilDBUpdateNewObjectType::addRBACOperations($type_id, array(ilDBUpdateNewObjectType::RBAC_OP_DELETE));

?>
<#3565>
<?php
$ilCtrlStructureReader->getStructure();
?>

<#3566>
<?php

	include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
	ilDBUpdateNewObjectType::deleteRBACOperation('sess',
		ilDBUpdateNewObjectType::getCustomRBACOperationId('edit_event')
	);
	
?>
<#3567>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
$type_id = ilDBUpdateNewObjectType::getObjectTypeId('trac');
ilDBUpdateNewObjectType::addRBACOperations($type_id, array(ilDBUpdateNewObjectType::RBAC_OP_WRITE));

?>
<#3568>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$dcl_type_id = ilDBUpdateNewObjectType::addNewType('dcl', 'Data Collection Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE,
	ilDBUpdateNewObjectType::RBAC_OP_COPY	
);
ilDBUpdateNewObjectType::addRBACOperations($dcl_type_id, $rbac_ops);

$parent_types = array('root', 'cat', 'crs', 'fold', 'grp');
ilDBUpdateNewObjectType::addRBACCreate('create_dcl', 'Create Data Collection', $parent_types);

// see 3675
// ilDBUpdateNewObjectType::addCustomRBACOperation($dcl_type_id, 'add_entry');

?>
<#3569>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#3570>
<?php

if (!$ilDB->tableColumnExists('tst_tests', 'offer_question_hints'))
{
	$ilDB->addTableColumn('tst_tests', 'offer_question_hints', array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	));
}

?>
<#3571>
<?php

if( !$ilDB->tableExists('qpl_hint_tracking') )
{
	$ilDB->createTable('qpl_hint_tracking', array(
		'qhtr_track_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'qhtr_active_fi' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'qhtr_pass' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'qhtr_question_fi' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'qhtr_hint_fi' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		)
	));

	$ilDB->addPrimaryKey('qpl_hint_tracking', array('qhtr_track_id'));
	
	$ilDB->createSequence('qpl_hint_tracking');
}

?>
<#3572>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3573>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3574>
<?php
	// taxonomy node assignment 
	$fields = array(
			'node_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'component' => array(
					'type' => 'text',
					'length' => 10,
					'notnull' => true
			),
			'item_type' => array(
					'type' => 'text',
					'length' => 20,
					'notnull' => true
			),
			'item_id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			)
	);
	$ilDB->createTable('tax_node_assignment', $fields);
	$ilDB->addPrimaryKey('tax_node_assignment', array('node_id', 'component', 'item_type', 'item_id'));
	$ilDB->addIndex("tax_node_assignment", array("component", "item_type", "item_id"), "i1");
?>
<#3575>
<?php
	if( !$ilDB->tableColumnExists('payment_prices', 'duration_from') )
	{
		$ilDB->addTableColumn("payment_prices", "duration_from",
			array('type' => 'date', 'notnull' => false
		));
	}
?>
<#3576>
<?php	
	if( !$ilDB->tableColumnExists('payment_prices', 'duration_until') )
	{
		$ilDB->addTableColumn("payment_prices", "duration_until",
			array('type' => 'date', 'notnull' => false
		));
	}
?>
<#3577>
<?php	
	if( !$ilDB->tableColumnExists('payment_prices', 'description') )
	{
		$ilDB->addTableColumn("payment_prices", "description",
		array(	'type' => 'text',
				'length' => 255,
				'notnull' => false,
				'fixed' => false
		));
	}
?>
<#3578>
<?php	
	if( !$ilDB->tableColumnExists('payment_prices', 'price_type') )
	{
		$ilDB->addTableColumn('payment_prices', 'price_type',
		array('type' => 'integer', 'length'  => 1,"notnull" => true,"default" => 1));
	}
?>
<#3579>
<?php	
	// migrate prices_table
	$ilDB->update('payment_prices', 
		array('price_type' => array('integer', 3)),
		array('unlimited_duration' => array('integer', 1)));
?>
<#3580>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3581>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3582>
<?php

	if( !$ilDB->tableColumnExists('tst_test_result', 'hint_count') )
	{
		$ilDB->addTableColumn('tst_test_result', 'hint_count', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}

	if( !$ilDB->tableColumnExists('tst_test_result', 'hint_points') )
	{
		$ilDB->addTableColumn('tst_test_result', 'hint_points', array(
			'type' => 'float',
			'notnull' => false,
			'default' => 0
		));
	}

?>

<#3583>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3584>
<?php

	if( !$ilDB->tableColumnExists('tst_pass_result', 'hint_count') )
	{
		$ilDB->addTableColumn('tst_pass_result', 'hint_count', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}

	if( !$ilDB->tableColumnExists('tst_pass_result', 'hint_points') )
	{
		$ilDB->addTableColumn('tst_pass_result', 'hint_points', array(
			'type' => 'float',
			'notnull' => false,
			'default' => 0
		));
	}

?>

<#3585>
<?php

	if( !$ilDB->tableColumnExists('tst_result_cache', 'hint_count') )
	{
		$ilDB->addTableColumn('tst_result_cache', 'hint_count', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}

	if( !$ilDB->tableColumnExists('tst_result_cache', 'hint_points') )
	{
		$ilDB->addTableColumn('tst_result_cache', 'hint_points', array(
			'type' => 'float',
			'notnull' => false,
			'default' => 0
		));
	}

?>
<#3586>
<?php
$ilDB->addTableColumn('glossary', 'show_tax', array(
	'type' => 'integer',
	'length' => 1,
	'notnull' => true,
	'default' => 0
));
?>
<#3587>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3588>
<?php
	// taxonomy properties 
	$fields = array(
			'id' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			),
			'sorting_mode' => array(
					'type' => 'integer',
					'length' => 4,
					'notnull' => true,
					'default' => 0
			)
	);
	$ilDB->createTable('tax_data', $fields);
	$ilDB->addPrimaryKey('tax_data', array('id'));
?>
<#3589>
<?php
	$set = $ilDB->query("SELECT * FROM object_data ".
		" WHERE type = ".$ilDB->quote("tax", "text")
		);
	while ($rec = $ilDB->fetchAssoc($set))
	{
		$ilDB->manipulate("INSERT INTO tax_data ".
			"(id, sorting_mode) VALUES (".
			$ilDB->quote($rec["obj_id"], "integer").",".
			$ilDB->quote(0, "integer").
			")");
	}
?>
<#3590>
<?php
	$ts_now = time();
	$ts_latest = mktime(23,55,00,date('n',time()),date('j',time()),date('Y',time()));

	// all limited course objects with ref_id and parent ref_id
	$query = "SELECT t.child,t.parent,c.activation_start,c.activation_end".
		" FROM crs_settings c".
		" JOIN object_reference r ON (r.obj_id = c.obj_id)".
		" JOIN tree t ON (r.ref_id = t.child)".
		" LEFT JOIN crs_items i ON (i.obj_id = r.ref_id)".
		" WHERE c.activation_type = ".$ilDB->quote(2, "integer").
		" AND i.timing_type IS NULL";
	$set = $ilDB->query($query);
	while($row = $ilDB->fetchAssoc($set))
	{				
		$query = "INSERT INTO crs_items (parent_id,obj_id,timing_type,timing_start,".
			"timing_end,suggestion_start,suggestion_end,changeable,earliest_start,".
			"latest_end,visible,position) VALUES (".
			$ilDB->quote($row["parent"],'integer').",".
			$ilDB->quote($row["child"],'integer').",".
			$ilDB->quote(0,'integer').",".
			$ilDB->quote($row["activation_start"],'integer').",".
			$ilDB->quote($row["activation_end"],'integer').",".
			$ilDB->quote($ts_now,'integer').",".
			$ilDB->quote($ts_now,'integer').",".
			$ilDB->quote(0,'integer').",".
			$ilDB->quote($ts_now,'integer').", ".
			$ilDB->quote($ts_latest,'integer').", ".
			$ilDB->quote(0,'integer').", ".
			$ilDB->quote(0,'integer').")";		
		$ilDB->manipulate($query);							
	}
?>
<#3591>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3592>
<?php
	$ilDB->addPrimaryKey('tax_tree', array('tax_tree_id', 'child'));
?>
<#3593>
<?php
$ilDB->addTableColumn('page_object', 'show_activation_info', array(
	'type' => 'integer',
	'length' => 1,
	'notnull' => true,
	'default' => 0
));
?>
<#3594>
<?php

	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_enabled') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_enabled', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}

	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_anon') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_anon', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_achieved_ts') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_achieved_ts', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_score') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_score', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_percentage') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_percentage', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_hints') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_hints', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_wtime') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_wtime', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_own_table') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_own_table', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_top_table') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_top_table', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
	
	if( !$ilDB->tableColumnExists('tst_tests', 'highscore_top_num') )
	{
		$ilDB->addTableColumn('tst_tests', 'highscore_top_num', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		));
	}
?>
<#3595>
<?php
	include_once "Services/Administration/classes/class.ilSetting.php";	
	$ilSetting = new ilSetting();
	$ilSetting->set("lp_desktop", 1);
	$ilSetting->set("lp_learner", 1);
?>
<#3596>
<?php

	$pfpg = array();

	$set = $ilDB->query("SELECT id,rep_obj_id".
				" FROM note".				
				" WHERE obj_type = ".$ilDB->quote("pf", "text").
				" AND obj_id = ".$ilDB->quote(0, "integer"));
	while($nt = $ilDB->fetchAssoc($set))
	{
		// get first page of portfolio
		if(!isset($pfpg[$nt["rep_obj_id"]]))
		{		
			$ilDB->setLimit(1);
			$fset = $ilDB->query("SELECT id".
				" FROM usr_portfolio_page".
				" WHERE portfolio_id  = ".$ilDB->quote($nt["rep_obj_id"], "integer").
				" AND type = ".$ilDB->quote(1, "integer").
				" ORDER BY order_nr ASC");		
			 $first = $ilDB->fetchAssoc($fset);			 
			 $pfpg[$nt["rep_obj_id"]] = $first["id"];
		}
		
		if($pfpg[$nt["rep_obj_id"]] && $nt["id"])
		{
			$ilDB->manipulate("UPDATE note".
				" SET obj_type = ".$ilDB->quote("pfpg", "text").
				", obj_id = ".$ilDB->quote($pfpg[$nt["rep_obj_id"]], "integer").
				" WHERE id = ".$ilDB->quote($nt["id"], "integer"));		
		}
	}
	
	unset($pfpg);
?>
<#3597>
<?php
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'main_table_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'is_online' => array(
			'type' => 'integer',
			'length' => 1,
		),
		'edit_type' => array(
			'type' => 'integer',
			'length' => 1,
		),
		'edit_start' => array(
			'type' => 'timestamp',
		),
		'edit_end' => array(
			'type' => 'timestamp',
		), 
		'rating' => array(
			'type' => 'integer',
			'length' => 1,
		),
		'public_notes' => array(
			'type' => 'integer',
			'length' => 1,
		),
		'approval' => array(
			'type' => 'integer',
			'length' => 1,
		),
		'notification' => array(
			'type' => 'integer',
			'length' => 1,
		)
	);
	
	$ilDB->createTable("il_dcl_data", $fields);
	$ilDB->addPrimaryKey("il_dcl_data", array("id"));
	$ilDB->createSequence("il_dcl_data");
?>
<#3598>
<?php	
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'table_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'type' => array(
			'type' => 'integer',
			'length' => 1,
		),
		'formtype' => array(
			'type' => 'integer',
			'length' => 1,
		),
	);
	$ilDB->createTable("il_dcl_view", $fields);
	$ilDB->addPrimaryKey("il_dcl_view", array("id"));
	$ilDB->createSequence("il_dcl_view");
?>
<#3599>
<?php
  $fields = array(
    'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'datatype_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => false
    ),
    'title' => array(
      'type' => 'text',
      'length' => 256,
      'notnull' => false
    ),
    'inputformat' => array(
      'type' => 'integer',
      'length' => 1,
      'notnull' => true
    ),
  );
  $ilDB->createTable("il_dcl_datatype_prop", $fields);
  $ilDB->addPrimaryKey("il_dcl_datatype_prop", array("id"));
?>
<#3600>
<?php	
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 256,
			'notnull' => false
		),
	);
	$ilDB->createTable("il_dcl_table", $fields);
	$ilDB->addPrimaryKey("il_dcl_table", array("id"));
	$ilDB->createSequence("il_dcl_table");
?>
<#3601>
<?php
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'table_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 256,
			'notnull' => false
		),
		'description' => array(
			'type' => 'text',
			'length' => 256,
			'notnull' => false
		),
		'datatype_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'required' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true
		),
	);
	
	$ilDB->createTable("il_dcl_field", $fields);
	$ilDB->addPrimaryKey("il_dcl_field", array("id"));
	$ilDB->createSequence("il_dcl_field");
?>
<#3602>
<?php
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'field_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
		),
		'datatype_prop_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'value' => array(
			'type' => 'text',
			'length' => 256,
			'notnull' => false
		),
	);
	$ilDB->createTable("il_dcl_field_prop", $fields);
	$ilDB->addPrimaryKey("il_dcl_field_prop", array("id"));
	$ilDB->createSequence("il_dcl_field_prop");
?>
<#3603>
<?php
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 256,
			'notnull' => false
		),
		'ildb_type' => array(
		'type' => 'text',
		'length' => 256,
		'notnull' => true
		),
		'storage_location' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
		),
	);
	$ilDB->createTable("il_dcl_datatype", $fields);
	$ilDB->addPrimaryKey("il_dcl_datatype", array("id"));
?>
<#3604>
<?php
		$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
			" VALUES (".
			$ilDB->quote(1, "integer").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(2, "integer").
			")");

		$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
			" VALUES (".
$ilDB->quote(2, "integer").", ".$ilDB->quote("text", "text").", ".$ilDB->quote("text", "text").", ".$ilDB->quote(1, "integer").
			")");

		$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
			" VALUES (".
			$ilDB->quote(3, "integer").", ".$ilDB->quote("reference", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(2, "integer").
			")");

		$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
			" VALUES (".
			$ilDB->quote(4, "integer").", ".$ilDB->quote("boolean", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(2, "integer").
			")");

		$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
			" VALUES (".
			$ilDB->quote(5, "integer").", ".$ilDB->quote("datetime", "text").", ".$ilDB->quote("date", "text").", ".$ilDB->quote(3, "integer").
			")");
?>
<#3605>
<?php
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'table_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
    'create_date' => array(
      'type' => 'date',
      'notnull' => false
    ),
    'last_update' => array(
      'type' => 'date',
      'notnull' => false
    ),
    'owner' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
	);
	
	$ilDB->createTable("il_dcl_record", $fields);
	$ilDB->addPrimaryKey("il_dcl_record", array("id"));
	$ilDB->createSequence("il_dcl_record");
?>
<#3606>
<?php	
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'record_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'field_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
		),
	);
	
	$ilDB->createTable("il_dcl_record_field", $fields);
	$ilDB->addPrimaryKey("il_dcl_record_field", array("id"));
	$ilDB->createSequence("il_dcl_record_field");	
?>
<#3607>
<?php	
  $fields = array(
  'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'record_field_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),    
    'value' => array(
      'type' => 'text',
      'length' => 4000,
      'notnull' => false
    ),
  ); 
  $ilDB->createTable("il_dcl_stloc1_value", $fields);
  $ilDB->addPrimaryKey("il_dcl_stloc1_value", array("id"));
  $ilDB->createSequence("il_dcl_stloc1_value");
?>
<#3608>
<?php
  $fields = array(
    'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'record_field_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),    
    'value' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => false
    ),
  ); 
  $ilDB->createTable("il_dcl_stloc2_value", $fields);
  $ilDB->addPrimaryKey("il_dcl_stloc2_value", array("id"));
  $ilDB->createSequence("il_dcl_stloc2_value");
?>
<#3609>
<?php
  $fields = array(
    'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'record_field_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),    
    'value' => array(
      'type' => 'timestamp',
      'notnull' => true
    ),
  ); 
  $ilDB->createTable("il_dcl_stloc3_value", $fields);
  $ilDB->addPrimaryKey("il_dcl_stloc3_value", array("id"));
  $ilDB->createSequence("il_dcl_stloc3_value");
?>
<#3610>
<?php
$ilDB->manipulate("INSERT INTO il_dcl_datatype_prop ".
			" (id, title, datatype_id, inputformat) VALUES (".
			$ilDB->quote(1, "integer").", ".$ilDB->quote("length", "text").", ".$ilDB->quote(2, "integer").", ".$ilDB->quote(1, "integer").
			")");

$ilDB->manipulate("INSERT INTO il_dcl_datatype_prop ".
			" (id, title, datatype_id, inputformat) VALUES (".
			$ilDB->quote(2, "integer").", ".$ilDB->quote("regex", "text").", ".$ilDB->quote(2, "integer").", ".$ilDB->quote(2, "integer").
			")");

$ilDB->manipulate("INSERT INTO il_dcl_datatype_prop ".
			" (id, title, datatype_id, inputformat) VALUES (".
			$ilDB->quote(3, "integer").", ".$ilDB->quote("table_id", "text").", ".$ilDB->quote(3, "integer").", ".$ilDB->quote(1, "integer").
			")");
?>
<#3611>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3612>
<?php
	if( !$ilDB->tableColumnExists('il_rating', 'category_id') )
	{
		$ilDB->addTableColumn('il_rating', 'category_id', array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0
		));
	}
?>
<#3613>
<?php
  $fields = array(
    'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'parent_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),    
    'title' => array(
      'type' => 'text',
      'length' => 100
    ),
	'description' => array(
      'type' => 'text',
      'length' => 1000
    ),
	'pos' => array(
      'type' => 'integer',
      'length' => 2,
      'notnull' => true
    )   
  ); 
  $ilDB->createTable("il_rating_cat", $fields);
  $ilDB->addPrimaryKey("il_rating_cat", array("id"));
  $ilDB->createSequence("il_rating_cat");
?>
<#3614>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3615>
<?php	
	$ilDB->dropPrimaryKey("il_rating");
	$ilDB->addPrimaryKey("il_rating", array("obj_id", "obj_type", "sub_obj_id", "sub_obj_type", "user_id", "category_id"));	
?>
<#3616>
<?php
	if( !$ilDB->tableColumnExists('il_wiki_data', 'rating_side') )
	{
		$ilDB->addTableColumn('il_wiki_data', 'rating_side', array(
			'type' => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default' => 0
		));
	}
	if( !$ilDB->tableColumnExists('il_wiki_data', 'rating_new') )
	{
		$ilDB->addTableColumn('il_wiki_data', 'rating_new', array(
			'type' => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default' => 0
		));
	}
	if( !$ilDB->tableColumnExists('il_wiki_data', 'rating_ext') )
	{
		$ilDB->addTableColumn('il_wiki_data', 'rating_ext', array(
			'type' => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default' => 0
		));
	}
?>
<#3617>
<?php
	if( !$ilDB->tableColumnExists('il_wiki_page', 'rating') )
	{
		$ilDB->addTableColumn('il_wiki_page', 'rating', array(
			'type' => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default' => 0
		));
	}
?>
<#3618>
<?php

	$wiki_ids = array();
	$set = $ilDB->query("SELECT id FROM il_wiki_data".
		" WHERE rating = ".$ilDB->quote(1, "integer"));		
	while($row = $ilDB->fetchAssoc($set))
	{
		$wiki_ids[] = $row["id"];
	}
	
	if($wiki_ids)
	{
		$ilDB->manipulate("UPDATE il_wiki_data".
			" SET rating_new = ".$ilDB->quote(1, "integer").
			" WHERE ".$ilDB->in("id", $wiki_ids, "", "integer"));
		
		$ilDB->manipulate("UPDATE il_wiki_page".
			" SET rating = ".$ilDB->quote(1, "integer").
			" WHERE ".$ilDB->in("wiki_id", $wiki_ids, "", "integer"));		
	}
?>
<#3619>
<?php
	$ts_now = time();
	$ts_latest = mktime(23,55,00,date('n',time()),date('j',time()),date('Y',time()));

	// all limited course objects with ref_id and parent ref_id
	$query = "SELECT t.child,t.parent,c.starting_time,c.ending_time".
		" FROM tst_tests c".
		" JOIN object_reference r ON (r.obj_id = c.obj_fi)".
		" JOIN tree t ON (r.ref_id = t.child)".
		" LEFT JOIN crs_items i ON (i.obj_id = r.ref_id)".
		" WHERE i.timing_type IS NULL";
	$set = $ilDB->query($query);
	while($row = $ilDB->fetchAssoc($set))
	{				
		if($row["starting_time"] || $row["ending_time"])
		{									
			$ts_start = time();
			if(preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $row["starting_time"], $d_parts))
			{			
				$ts_start = mktime(
					isset($d_parts[4]) ? $d_parts[4] : 0, 
					isset($d_parts[5]) ? $d_parts[5] : 0,
					isset($d_parts[6]) ? $d_parts[6] : 0,
					$d_parts[2],
					$d_parts[3],
					$d_parts[1]);
			}
			$ts_end = mktime(0, 0, 1, 1, 1, date("Y")+3);
			if(preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $row["ending_time"], $d_parts))
			{			
				$ts_end = mktime(
					isset($d_parts[4]) ? $d_parts[4] : 0, 
					isset($d_parts[5]) ? $d_parts[5] : 0,
					isset($d_parts[6]) ? $d_parts[6] : 0,
					$d_parts[2],
					$d_parts[3],
					$d_parts[1]);
			}
			
			$query = "INSERT INTO crs_items (parent_id,obj_id,timing_type,timing_start,".
				"timing_end,suggestion_start,suggestion_end,changeable,earliest_start,".
				"latest_end,visible,position) VALUES (".
				$ilDB->quote($row["parent"],'integer').",".
				$ilDB->quote($row["child"],'integer').",".
				$ilDB->quote(0,'integer').",".
				$ilDB->quote($ts_start,'integer').",".
				$ilDB->quote($ts_end,'integer').",".
				$ilDB->quote($ts_now,'integer').",".
				$ilDB->quote($ts_now,'integer').",".
				$ilDB->quote(0,'integer').",".
				$ilDB->quote($ts_now,'integer').", ".
				$ilDB->quote($ts_latest,'integer').", ".
				$ilDB->quote(1,'integer').", ".
				$ilDB->quote(0,'integer').")";		
			$ilDB->manipulate($query);				
		}
	}
?>
<#3620>
<?php

	if( $ilDB->tableColumnExists("tst_tests", "starting_time") )
	{
		$ilDB->dropTableColumn("tst_tests", "starting_time");
	}
	if( $ilDB->tableColumnExists("tst_tests", "ending_time") )
	{
		$ilDB->dropTableColumn("tst_tests", "ending_time");
	}

?>
<#3621>
<?php

$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
		" VALUES (".
		$ilDB->quote(6, "integer").", ".$ilDB->quote("file", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(2, "text").
		")");
		
?>
<#3622>
<?php
if (!$ilDB->tableColumnExists('media_item', 'highlight_mode'))
{
	$ilDB->addTableColumn("media_item", "highlight_mode", array(
		"type" => "text",
		"notnull" => false,
		"length" => 8,
		"fixed" => false));
}
?>
<#3623>
<?php	
if (!$ilDB->tableColumnExists('media_item', 'highlight_class'))
{
	$ilDB->addTableColumn("media_item", "highlight_class", array(
		"type" => "text",
		"notnull" => false,
		"length" => 8,
		"fixed" => false));
}
?>
<#3624>
<?php

	if (!$ilDB->tableColumnExists('booking_object', 'pool_id'))
	{
		$ilDB->addTableColumn("booking_object", "pool_id", array(
			"type" => "integer",
			"notnull" => false,
			"length" => 4,
			"default" => 0));
	}

	$types = $ilDB->query("SELECT * FROM booking_type");
	while($row = $ilDB->fetchAssoc($types))
	{
		$sql = "UPDATE booking_object SET".
			" pool_id = ".$ilDB->quote($row["pool_id"], "integer");
		if($row["schedule_id"])
		{
			$sql .= ",schedule_id = ".$ilDB->quote($row["schedule_id"], "integer");
		}				
		$sql .= ", title = CONCAT(title, ".$ilDB->quote(" (".$row["title"].")", "text").")".
			" WHERE type_id = ".$ilDB->quote($row["booking_type_id"], "integer");
		
		$ilDB->manipulate($sql);		
	}
	
	if( $ilDB->tableColumnExists("booking_object", "type_id") )
	{
		$ilDB->dropTableColumn("booking_object", "type_id");
		$ilDB->dropTable("booking_type"); 
	}
		
?>
<#3625>
<?php

	if (!$ilDB->tableColumnExists('booking_object', 'description'))
	{
		$ilDB->addTableColumn("booking_object", "description", array(
			"type" => "text",
			"notnull" => false,
			"length" => 1000,
			"fixed" => false));
	}
	if (!$ilDB->tableColumnExists('booking_object', 'nr_items'))
	{
		$ilDB->addTableColumn("booking_object", "nr_items", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 2,
			"default" => 1));
	}

?>
<#3626>
<?php

	if (!$ilDB->tableColumnExists('booking_settings', 'schedule_type'))
	{
		$ilDB->addTableColumn("booking_settings", "schedule_type", array(
			"type" => "integer",
			"notnull" => true,
			"length" => 1,
			"default" => 1));
	}
	
?>
<#3627>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3628>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$poll_type_id = ilDBUpdateNewObjectType::addNewType('poll', 'Poll Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE,
	ilDBUpdateNewObjectType::RBAC_OP_COPY	
);
ilDBUpdateNewObjectType::addRBACOperations($poll_type_id, $rbac_ops);

$parent_types = array('root', 'cat', 'crs', 'fold', 'grp');
ilDBUpdateNewObjectType::addRBACCreate('create_poll', 'Create Poll', $parent_types);

// see 3675
// ilDBUpdateNewObjectType::addCustomRBACOperation($poll_type_id, 'add_entry');

?>
<#3629>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3630>
<?php
  $fields = array(
    'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'question' => array(
      'type' => 'text',
      'length' => 1000
    ),    
    'image' => array(
      'type' => 'text',
      'length' => 1000
    ),
	'online_status' => array(
      'type' => 'integer',
      'length' => 1,
	  'notnull' => true,
	  'default' => 0
    ),
	'view_results' => array(
      'type' => 'integer',
      'length' => 1,
	  'notnull' => true,
	  'default' => 3
    )   
  ); 
  $ilDB->createTable("il_poll", $fields);
  $ilDB->addPrimaryKey("il_poll", array("id"));
?>
<#3631>
<?php
  $fields = array(
    'id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'poll_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'answer' => array(
      'type' => 'text',
      'length' => 1000
    ),	
	'pos' => array(
      'type' => 'integer',
      'length' => 2,
	  'notnull' => true		
    )   
  ); 
  $ilDB->createTable("il_poll_answer", $fields);
  $ilDB->addPrimaryKey("il_poll_answer", array("id"));
  $ilDB->createSequence("il_poll_answer");
?>
<#3632>
<?php
  $fields = array(
    'user_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'poll_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    ),
    'answer_id' => array(
      'type' => 'integer',
      'length' => 4,
      'notnull' => true
    )
  ); 
  $ilDB->createTable("il_poll_vote", $fields);
  $ilDB->addPrimaryKey("il_poll_vote", array("user_id", "poll_id"));
?>
<#3633>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3634>
<?php
$ilDB->addIndex("bookmark_tree", array("child", "tree"), "i3");
?>
<#3635>
<?php
	$ilDB->modifyTableColumn('il_dcl_record', 'create_date', 
								array("type" => "timestamp"));
?>
<#3636>
<?php
	$ilDB->modifyTableColumn('il_dcl_record', 'last_update', 
								array("type" => "timestamp"));
?>
<#3637>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3638>
<?php
	$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'view_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'field' => array(
		'type' 		=> 'text',
		'length' 	=> 255,
		'notnull'	=> true
	),
	'field_order' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
  ); 
  $ilDB->createTable("il_dcl_viewdefinition", $fields);
  $ilDB->addPrimaryKey("il_dcl_viewdefinition", array("id"));
  $ilDB->createSequence("il_dcl_viewdefinition");
?>
<#3639>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3640>
<?php
	$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'ass_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	)
  ); 
  $ilDB->createTable("il_exc_team", $fields);
  $ilDB->addPrimaryKey("il_exc_team", array("ass_id", "user_id"));
  $ilDB->createSequence("il_exc_team");
?>
<#3641>
<?php
	$fields = array(
	'team_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'user_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'details' => array(
		'type' => 'text',
		'length' => 500,
		'notnull' => false
	),
	'action' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true
	),
	'tstamp' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
  ); 
  $ilDB->createTable("il_exc_team_log", $fields);
?>
<#3642>
<?php
// Fetch orphaned entries of table "mail_attachment"
$res = $ilDB->query('
	SELECT mattorphaned.mail_id, mattorphaned.path
	FROM mail_attachment mattorphaned
	WHERE mattorphaned.mail_id NOT IN (
		SELECT matt.mail_id 
		FROM mail_attachment matt
		INNER JOIN mail 
			ON mail.mail_id = matt.mail_id 
		INNER JOIN usr_data
			ON usr_data.usr_id = mail.user_id
	)'
);

// Helper array to collect paths of orphaned entries
$paths = array();

$stmt = $ilDB->prepareManip('DELETE FROM mail_attachment WHERE mail_id = ?', array('integer'));
// Delete the entries and store the path to check if it is shared with residual entries in the next step
while($row = $ilDB->fetchAssoc($res))
{
	$ilDB->execute($stmt, array($row['mail_id']));

	// Save path in key to prevent unnecessary lookups for duplicates
	isset($paths[$row['path']]) ? $paths[$row['path']]++ : $paths[$row['path']] = 0;
} 
$ilDB->free($stmt);

/***************************/

$stmt = $ilDB->prepare('SELECT COUNT(mail_id) cnt FROM mail_attachment WHERE path = ?', array('text'));
foreach($paths as $path => $number)
{
	$res = $ilDB->execute($stmt, array($path));
	$row = $ilDB->fetchAssoc($res);

	// Check if the path is used by residual entries
	if(isset($row['cnt']) && $row['cnt'] == 0)
	{
		try
		{
			// Delete the directory recursively
			$basedirectory = CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.$path;

			$iter = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($basedirectory), RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach($iter as $file)
			{
				/**
 				 * @var $file SplFileInfo
				 */
				$filepath = $file->getPathname();
				$bool = false;

				if($file->isDir())
				{
					$bool = @rmdir($file->getPathname());
				}
				else
				{
					$bool = @unlink($file->getPathname());
				}

				if($bool)
				{
					$GLOBALS['ilLog']->write('Database Update: Deletion of file/subdirectory '.$filepath.' finished.');
				}
				else
				{
					$GLOBALS['ilLog']->write('Database Update: Deletion of file/subdirectory '.$filepath.' failed.');
				}
			}
			
			// Finally delete the base directory
			$bool = @rmdir($basedirectory);
			if($bool)
			{
				$GLOBALS['ilLog']->write('Database Update: Deletion of base directory '.$basedirectory.' finished.');
			}
			else
			{
				$GLOBALS['ilLog']->write('Database Update: Deletion of base directory '.$basedirectory.' failed.');
			}
		}
		catch(Exception $e) { }
	}
}
$ilDB->free($stmt);
?>
<#3643>
<?php
// Delete all mails without an existing owner
if($ilDB->getDBType() == 'mysql' || $ilDB->getDBType() == 'innodb')
{
	$ilDB->manipulate('
		DELETE m1
		FROM mail m1
		INNER JOIN (
			SELECT mail.mail_id
			FROM mail
			LEFT JOIN usr_data
				ON usr_data.usr_id = mail.user_id
			WHERE usr_data.usr_id IS NULL
		) m2
		ON m2.mail_id = m1.mail_id');
}
else
{
	// Oracle and Postgres
	$ilDB->manipulate(' 
		DELETE FROM mail
		WHERE mail.mail_id IN (
			SELECT mail.mail_id
			FROM mail
			LEFT JOIN usr_data
				ON usr_data.usr_id = mail.user_id
			WHERE usr_data.usr_id IS NULL
		)');
}
?>
<#3644>
<?php
$stmt = $ilDB->prepare('SELECT COUNT(mail_id) cnt FROM mail_attachment WHERE '.$ilDB->like('path', 'text', '?'), array('text'));
try
{
	$iter = new RegexIterator(
		new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(CLIENT_DATA_DIR.DIRECTORY_SEPARATOR.'mail'),
		RecursiveIteratorIterator::SELF_FIRST), '/mail_\d+_\d+$/'
	);
	foreach($iter as $file)
	{
		/**
		 * @var $file SplFileInfo
		 */
		if($file->isDir())
		{
			$path = $file->getPathname();

			$res = $ilDB->execute($stmt, array('%/'.$file->getFilename()));
			
			$row = $ilDB->fetchAssoc($res);
			if(isset($row['cnt']) && $row['cnt'] == 0)
			{
				$basedirectory = $file->getPathname();
				
				$GLOBALS['ilLog']->write('Database Update: Directory '.$basedirectory.' not in use anymore. Processing deletion ...');
				try
				{
					$delete_iter = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($basedirectory), RecursiveIteratorIterator::CHILD_FIRST
					);
					foreach($delete_iter as $file_to_delete)
					{
						/**
						 * @var $file_to_delete SplFileInfo
						 */
						$filepath = $file_to_delete->getPathname();
						$bool = false;

						if($file_to_delete->isDir())
						{
							$bool = @rmdir($file_to_delete->getPathname());
						}
						else
						{
							$bool = @unlink($file_to_delete->getPathname());
						}

						if($bool)
						{
							$GLOBALS['ilLog']->write('Database Update: Deletion of file/subdirectory '.$filepath.' finished.');
						}
						else
						{
							$GLOBALS['ilLog']->write('Database Update: Deletion of file/subdirectory '.$filepath.' failed.');
						}
					}
					
					$bool = @rmdir($basedirectory);
					if($bool)
					{
						$GLOBALS['ilLog']->write('Database Update: Deletion of base directory '.$basedirectory.' finished.');
					}
					else
					{
						$GLOBALS['ilLog']->write('Database Update: Deletion of base directory '.$basedirectory.' failed.');
					}
				}
				catch(Exception $e) { }
			}
		}
	}
}
catch(Exception $e) { }
$ilDB->free($stmt);
?>
<#3645>
<?php

	if (!$ilDB->tableColumnExists('qpl_qst_mc', 'feedback_setting'))
	{
		$ilDB->addTableColumn('qpl_qst_mc', 'feedback_setting', array(
			"type" => "integer",
			"notnull" => true,
			"length" => 1,
			"default" => 1));
	}
?>
<#3646>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3647>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3648>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3649>
<?php

	if(!$ilDB->tableColumnExists('container_reference','title_type'))
	{
		$ilDB->addTableColumn('container_reference', 'title_type', array(
			"type" => "integer",
			"notnull" => true,
			"length" => 1,
			"default" => 1));
	}
?>
<#3650>
<?php

	if (!$ilDB->tableExists('qpl_fb_cloze'))
	{
		$fields = array(
			'feedback_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'question_fi' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'answer' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'feedback' => array(
				'type' => 'text',
				'length' => 4000,
				'notnull' => false
			),
			'tstamp' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			)
		);
		
		$ilDB->createTable('qpl_fb_cloze', $fields);
		$ilDB->addIndex('qpl_fb_cloze', array('question_fi'), 'i1');
		$ilDB->createSequence('qpl_fb_cloze');
	}
?>
<#3651>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3652>
<?php

	if (!$ilDB->tableExists('qpl_fb_errortext'))
	{
		$fields = array(
			'feedback_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'question_fi' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'answer' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'feedback' => array(
				'type' => 'text',
				'length' => 4000,
				'notnull' => false
			),
			'tstamp' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			)
		);
		
		$ilDB->createTable('qpl_fb_errortext', $fields);
		$ilDB->addIndex('qpl_fb_errortext', array('question_fi'), 'i1');
		$ilDB->createSequence('qpl_fb_errortext');
	}
?>
<#3653>
<?php

	if (!$ilDB->tableExists('qpl_fb_matching'))
	{
		$fields = array(
			'feedback_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'question_fi' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'answer' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'feedback' => array(
				'type' => 'text',
				'length' => 4000,
				'notnull' => false
			),
			'tstamp' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			)
		);
		
		$ilDB->createTable('qpl_fb_matching', $fields);
		$ilDB->addIndex('qpl_fb_matching', array('question_fi'), 'i1');
		$ilDB->createSequence('qpl_fb_matching');
	}
?>
<#3654>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3655>
<?php

	if(!$ilDB->tableColumnExists('cal_categories','loc_type'))
	{
		$ilDB->addTableColumn('cal_categories', 'loc_type', array(
			"type" => "integer",
			"notnull" => true,
			"length" => 1,
			"default" => 1));
	}
	if(!$ilDB->tableColumnExists('cal_categories','remote_url'))
	{
		$ilDB->addTableColumn('cal_categories', 'remote_url', array(
			"type" => "text",
			"notnull" => false,
			"length" => 500
		));
	}
	if(!$ilDB->tableColumnExists('cal_categories','remote_user'))
	{
		$ilDB->addTableColumn('cal_categories', 'remote_user', array(
			"type" => "text",
			"notnull" => false,
			"length" => 50
		));
	}
	if(!$ilDB->tableColumnExists('cal_categories','remote_pass'))
	{
		$ilDB->addTableColumn('cal_categories', 'remote_pass', array(
			"type" => "text",
			"notnull" => false,
			"length" => 50
		));
	}
?>

<#3656>
<?php

if(!$ilDB->tableExists('syst_style_cat'))
{
	$fields = array(
		'skin_id'	=> array(
			'type'		=> 'text',
			'length'	=> 100,
			'fixed'			=> false,
			'notnull'		=> true
		),
		'style_id'	=> array(
			'type'		=> 'text',
			'length'	=> 100,
			'fixed'			=> false,
			'notnull'		=> true
		),
		'category_ref_id' 		=> array(
			'type' 			=> 'integer',
			'length' 		=> 1,
		)
	);
	$ilDB->createTable('syst_style_cat',$fields);
	$ilDB->addPrimaryKey('syst_style_cat',array('skin_id', 'style_id', 'category_ref_id'));
}
?>
<#3657>
<?php
$ilDB->dropTable("syst_style_cat");
$fields = array(
	'skin_id'	=> array(
		'type'		=> 'text',
		'length'	=> 50,
		'fixed'			=> false,
		'notnull'		=> true
	),
	'style_id'	=> array(
		'type'		=> 'text',
		'length'	=> 50,
		'fixed'			=> false,
		'notnull'		=> true
	),
	'substyle'	=> array(
		'type'		=> 'text',
		'length'	=> 50,
		'fixed'			=> false,
		'notnull'		=> true
	),
	'category_ref_id' 		=> array(
		'type' 			=> 'integer',
		'length' 		=> 1,
	)
);

$ilDB->createTable('syst_style_cat',$fields);
$ilDB->addPrimaryKey('syst_style_cat',array('skin_id', 'style_id', 'substyle', 'category_ref_id'));

?>
<#3658>
<?php

	$ilDB->manipulate("DELETE FROM syst_style_cat");
	
	$ilDB->dropPrimaryKey('syst_style_cat');
	$ilDB->dropTableColumn('syst_style_cat', 'category_ref_id');
	
	$ilDB->addTableColumn("syst_style_cat", "category_ref_id", array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true,
		'default' => 0
		));
	
	$ilDB->addPrimaryKey('syst_style_cat',array('skin_id', 'style_id', 'substyle', 'category_ref_id'));
?>
<#3659>
<?php
	include_once("./Services/Migration/DBUpdate_3136/classes/class.ilDBUpdate3136.php");
	ilDBUpdate3136::addStyleClass("AdvancedKnowledge", "section", "div",
				array("margin-bottom" => "20px",
					  "margin-top" => "20px",
					  "background-color" => "#FFF9EF",
					  "border-color" => "#FFDDA5",
					  "border-style" => "solid",
					  "border-width" => "1px",
					  "padding-bottom" => "10px",
					  "padding-top" => "10px",
					  "padding-right" => "20px",
					  "padding-left" => "20px",
					  "background-image" => "advknow.png",
					  "background-repeat" => "no-repeat",
					  "background-position" => "right top",
					  ));
?>
<#3660>
<?php
		// check, whether core style class exists
		$sets = $ilDB->query("SELECT * FROM object_data WHERE type = 'sty'");
		
		while ($rec = $ilDB->fetchAssoc($sets))
		{
			// now check, whether some core image files are missing
			ilUtil::makeDir(CLIENT_WEB_DIR."/sty");
			ilUtil::makeDir(CLIENT_WEB_DIR."/sty/sty_".$rec["obj_id"]);
			$tdir = CLIENT_WEB_DIR."/sty/sty_".$rec["obj_id"]."/images";
			ilUtil::makeDir($tdir);
			$sfile = "./Services/Style/basic_style/images/advknow.png";
			$cim = "advknow.png";
			if (!is_file($tdir."/".$cim) && is_file($sfile))
			{
				copy($sfile, $tdir."/".$cim);
			}
		}

?>
<#3661>
<?php
$fields = array(
	'obj_id'	=> array(
		'type'		=> 'integer',
		'length'	=> 4,
		'notnull'		=> true
	)
);

$ilDB->createTable('il_certificate',$fields);
$ilDB->addPrimaryKey('il_certificate',array('obj_id'));

?>
<#3662>
<?php

$cdirs = array(
	CLIENT_WEB_DIR . "/course/certificates/",
	CLIENT_WEB_DIR . "/exercise/certificates/",
	CLIENT_WEB_DIR . "/certificates/scorm/",
	CLIENT_WEB_DIR . "/certificates/skill/",
	CLIENT_WEB_DIR . "/assessment/certificates/"
);
$coids = array();
foreach($cdirs as $cdir)
{
	foreach(glob($cdir."*", GLOB_ONLYDIR) as $codir)
	{
		$coids[] = str_replace($cdir, "", $codir);		
	}	
}
foreach($coids as $coid)
{
	$ilDB->insert("il_certificate", array("obj_id"=>array("integer", $coid)));	
}

?>
<#3663>
<?php
if(!$ilDB->tableColumnExists('tst_tests','specific_feedback'))
{
	$ilDB->addTableColumn('tst_tests', 'specific_feedback', array(
		"type" => "integer",
		"notnull" => false,
		"length" => 4,
		'default' => 0
	));
}
?>

<#3664>
<?php
	
	$ilDB->renameTable('crs_members','obj_members');
?>

<#3665>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
$hlps_type_id = ilDBUpdateNewObjectType::addNewType('hlps', 'Help Settings');

$obj_id = $ilDB->nextId('object_data');
$ilDB->manipulate("INSERT INTO object_data ".
	"(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
	$ilDB->quote($obj_id, "integer").",".
	$ilDB->quote("hlps", "text").",".
	$ilDB->quote("HelpSettings", "text").",".
	$ilDB->quote("Help Settings", "text").",".
	$ilDB->quote(-1, "integer").",".
	$ilDB->now().",".
	$ilDB->now().
	")");

$ref_id = $ilDB->nextId('object_reference');
$ilDB->manipulate("INSERT INTO object_reference ".
	"(obj_id, ref_id) VALUES (".
	$ilDB->quote($obj_id, "integer").",".
	$ilDB->quote($ref_id, "integer").
	")");

// put in tree
$tree = new ilTree(ROOT_FOLDER_ID);
$tree->insertNode($ref_id,SYSTEM_FOLDER_ID);


$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE
);
ilDBUpdateNewObjectType::addRBACOperations($hlps_type_id, $rbac_ops);

?>
<#3666>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3667>
<?php
	
#9350: removing visible permission for side blocks (feed, poll)
include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
ilDBUpdateNewObjectType::deleteRBACOperation('poll', 
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE);
ilDBUpdateNewObjectType::deleteRBACOperation('feed', 
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE);

?>
<#3668>
<?php
	$fields = array (
		'id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
		);
	$ilDB->createTable('help_file', $fields);
	$ilDB->addPrimaryKey('help_file', array('id'));
	$ilDB->createSequence("help_file");
?>
<#3669>
<?php
$ilDB->dropSequence("help_file");
$ilDB->dropTable("help_file");
?>
<#3670>
<?php
	$fields = array (
		'id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
		'lm_id'    => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
		);
	$ilDB->createTable('help_module', $fields);
	$ilDB->addPrimaryKey('help_module', array('id'));
	$ilDB->createSequence("help_module");
?>
<#3671>
<?php
$ilDB->addTableColumn("help_tooltip", "lang", array(
	"type" => "text",
	"notnull" => true,
	"length" => 2,
	"fixed" => true,
	"default" => "de"));
?>
<#3672>
<?php
$ilDB->addTableColumn("help_tooltip", "module_id", array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0));
?>
<#3673>
<?php
$ilDB->addTableColumn("help_map", "module_id", array(
	"type" => "integer",
	"notnull" => true,
	"length" => 4,
	"default" => 0));
?>
<#3674>
<?php
$ilDB->dropPrimaryKey("help_map");
$ilDB->addPrimaryKey('help_map', array('component', 'screen_id', 'screen_sub_id', 'chap', 'perm', 'module_id'));
?>
<#3675>
<?php

// #9396: fixing custom rbac operations for dcl, poll
include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
$poll_type_id = ilDBUpdateNewObjectType::getObjectTypeId('poll');
if($poll_type_id)
{
	$ilDB->manipulate('DELETE FROM rbac_operations WHERE operation = '.
		$ilDB->quote($poll_type_id, 'text'));
}
$dcl_type_id = ilDBUpdateNewObjectType::getObjectTypeId('dcl');
if($dcl_type_id)
{
	$ilDB->manipulate('DELETE FROM rbac_operations WHERE operation = '.
		$ilDB->quote($dcl_type_id, 'text'));
	
	// re-doing dcl
	$ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('add_entry', 'Add Entry', 'object', 3200);
	if($ops_id)
	{
		ilDBUpdateNewObjectType::addRBACOperation($dcl_type_id, $ops_id);
	}
}

?>
<#3676>
<?php

#6969
include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
ilDBUpdateNewObjectType::updateOperationOrder('invite', 2600);

?>
<#3677>
<?php
global $ilDB;

if(!$ilDB->tableExists('ecs_node_mapping_a'))
{
	$fields = array(
		'server_id' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'cs_root' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'cs_id' => array(
			'type' => 'integer',
			'length' => 4
		),
		'ref_id' => array(
			'type' => 'integer',
			'length' => 4
		),
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4
		),
		'title_update' => array(
			'type' => 'integer',
			'length' => 1
		),
		'position_update' => array(
			'type' => 'integer',
			'length' => 1
		),
		'tree_update' => array(
			'type' => 'integer',
			'length' => 1
		)
	);
	$ilDB->createTable('ecs_node_mapping_a', $fields);
	$ilDB->addPrimaryKey('ecs_node_mapping_a', array('server_id', 'mid', 'cs_root', 'cs_id'));
}
?>

<#3678>
<?php
global $ilDB;

if(!$ilDB->tableExists('ecs_cms_tree'))
{
	$fields = array(
		'tree' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'child' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'parent' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'lft' => array(
			'type' => 'integer',
			'length' => 4
		),
		'rgt' => array(
			'type' => 'integer',
			'length' => 4
		),
		'depth' => array(
			'type' => 'integer',
			'length' => 4
		)
	);
	$ilDB->createTable('ecs_cms_tree', $fields);
	$ilDB->addPrimaryKey('ecs_cms_tree', array('tree', 'child'));
}
if(!$ilDB->tableExists('ecs_cms_data'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'server_id' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'tree_id' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'cms_id' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'title' => array(
			'type' => 'text',
			'length' => 512,
			'notnull' => false
		)
	);
	$ilDB->createTable('ecs_cms_data', $fields);
	$ilDB->addPrimaryKey('ecs_cms_data', array('obj_id'));
	$ilDB->createSequence('ecs_cms_data');
}
?>
<#3679>
<?php

	global $ilDB;

	if(!$ilDB->tableColumnExists('ecs_cms_data','term'))
	{
		$ilDB->addTableColumn(
			'ecs_cms_data',
			'term',
			array(
				'type'	=> 'text',
				'length' => 255,
				'notnull' => false
			)
		);
	}
?>
<#3680>
<?php
	global $ilDB;

	if(!$ilDB->tableColumnExists('ecs_cms_data','status'))
	{
		$ilDB->addTableColumn(
			'ecs_cms_data',
			'status',
			array(
				'type'	=> 'integer',
				'length' => 2,
				'notnull' => true,
				'default' => 1
			)
		);
	}
?>
<#3681>
<?php
	// original update step (#3395) was broken
	if(!$ilDB->sequenceExists('ecs_container_mapping'))
	{
		$res = $ilDB->query('SELECT mapping_id FROM ecs_container_mapping');
		$rows = $res->numRows();
		$ilDB->createSequence('ecs_container_mapping',++$rows);
	}
?>
<#3682>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3683>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3684>
<?php
if(!$ilDB->tableExists('qpl_a_essay'))
{
	$fields = array(
		'answer_id' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'question_fi' => array(
			'type' => 'integer',
			'length' => 4,
		),
		'answertext' => array(
			'type' => 'text',
			'length' => 1000,
			'notnull' => false
		),		
		'points' => array(
			'type' => 'integer',
			'length' => 4,
		)
	);
	$ilDB->createTable('qpl_a_essay', $fields);
	$ilDB->addPrimaryKey('qpl_a_essay', array('answer_id'));
	$ilDB->createSequence('qpl_a_essay');	
}
?>
<#3685>
<?php
	if(!$ilDB->tableColumnExists('qpl_qst_essay','keyword_relation'))
	{
		$ilDB->addTableColumn(
			'qpl_qst_essay',
			'keyword_relation',
			array(
				'type'	=> 'text',
				'length' => 3,
				'notnull' => true,
				'default' => 'any'
			)
		);
	}
?>
<#3686>
<?php
	if (!$ilDB->tableExists('qpl_fb_essay'))
	{
		$fields = array(
			'feedback_id' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'question_fi' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'answer' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			),
			'feedback' => array(
				'type' => 'text',
				'length' => 4000,
				'notnull' => false
			),
			'tstamp' => array(
				'type' => 'integer',
				'length' => 4,
				'notnull' => true
			)
		);

		$ilDB->createTable('qpl_fb_essay', $fields);
		$ilDB->addIndex('qpl_fb_essay', array('question_fi'), 'i1');
		$ilDB->createSequence('qpl_fb_essay');
	}
?>
<#3687>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3688>
<?php
	$ilDB->manipulateF(
		'UPDATE rbac_operations SET operation = %s WHERE operation = %s',
		array('text', 'text'),
		array('internal_mail', 'mail_visible')
	);
?>
<#3689>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$itgr_type_id = ilDBUpdateNewObjectType::addNewType('itgr', 'Item Group');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE,
	ilDBUpdateNewObjectType::RBAC_OP_COPY	
);
ilDBUpdateNewObjectType::addRBACOperations($itgr_type_id, $rbac_ops);

$parent_types = array('root', 'cat', 'crs', 'fold', 'grp');
ilDBUpdateNewObjectType::addRBACCreate('create_itgr', 'Create Item Group', $parent_types);

?>
<#3690>
<?php
	$ilCtrlStructureReader->getStructure();
?>

<#3691>
<?php

	// table fields used by tst's obligate test questions (elba steps 21, 22, 23 and 24)

	if(!$ilDB->tableColumnExists('tst_test_question','obligatory'))
	{
		$ilDB->addTableColumn("tst_test_question", "obligatory", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 0
		));
	}

	if(!$ilDB->tableColumnExists('tst_test_result','answered'))
	{
		$ilDB->addTableColumn("tst_test_result", "answered", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 1
		));
	}

	if(!$ilDB->tableColumnExists('tst_pass_result','obligations_answered'))
	{
		$ilDB->addTableColumn("tst_pass_result", "obligations_answered", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 1
		));
	}

	if(!$ilDB->tableColumnExists('tst_result_cache','obligations_answered'))
	{
		$ilDB->addTableColumn("tst_result_cache", "obligations_answered", array(
			"type" => "integer",
			"length" => 1,
			"notnull" => true,
			"default" => 1
		));
	}
 	
?>
<#3692>
<?php

$fields = array (
	'item_group_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0),
	'item_ref_id' => array ('type' => 'integer', 'length'  => 4,'notnull' => true, 'default' => 0)
);
$ilDB->createTable('item_group_item', $fields);
$ilDB->addPrimaryKey('item_group_item', array('item_group_id', 'item_ref_id'));

?>
<#3693>
<?php
	$ilDB->manipulateF(
		'UPDATE rbac_operations SET operation = %s WHERE operation = %s',
		array('text', 'text'),
		array('add_reply', 'add_post')
	);
?>
<#3694>
<?php
	$ilDB->manipulateF(
		'UPDATE rbac_operations SET description = %s WHERE operation = %s',
		array('text', 'text'),
		array('Reply to forum articles', 'add_reply')
	);
?>
<#3695>
<?php

if (!$ilDB->tableColumnExists('tst_tests', 'obligations_enabled'))
{
	$ilDB->addTableColumn('tst_tests', 'obligations_enabled', array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	));
}

?>
<#3696>
<?php

$text_questions = $ilDB->query(
	'SELECT question_fi, keywords, points
	 FROM qpl_questions
	 JOIN qpl_qst_essay ON qpl_questions.question_id = qpl_qst_essay.question_fi 
	 WHERE keywords IS NOT NULL'
	);

while ($row = $ilDB->fetchAssoc($text_questions))
{
	$points = $row['points'];
	foreach (preg_split ("/[\s, ]+/", $row['keywords']) as $keyword)
	{
		$keyword = trim($keyword);
		if (strlen($keyword))
		{
			$nextId = $ilDB->nextId('qpl_a_essay');
			$query = 'INSERT INTO qpl_a_essay (answer_id, question_fi, answertext, points) VALUES (%s, %s, %s, %s)';
			$types = array("integer", "integer", "text", "integer");
			$values = array($nextId, $row['question_fi'], $keyword, $points);
			$ilDB->manipulateF($query, $types, $values);
			$points = 0;
		}
	}
}
?>
<#3697>
<?php
if(!$ilDB->tableColumnExists('sahs_lm', 'auto_last_visited'))
{
	$ilDB->addTableColumn(
		'sahs_lm',
		'auto_last_visited',
		array(
			'type'    => 'text',
			'length'  => 1,
			'notnull' => true,
			'default' => 'y'
		)
	);
	$ilDB->query("UPDATE sahs_lm SET auto_last_visited = 'n'");
}
?>
<#3698>
<?php
if(!$ilDB->tableColumnExists('sahs_lm', 'check_values'))
{
	$ilDB->addTableColumn(
		'sahs_lm',
		'check_values',
		array(
			 'type'    => 'text',
			 'length'  => 1,
			 'notnull' => true,
			 'default' => 'y'
		)
	);
	$ilDB->query("UPDATE sahs_lm SET check_values = 'y'");
}
?>
<#3699>
<?php
// remove obsolete id columns
if($ilDB->tableColumnExists('il_dcl_viewdefinition','id'))
{
	$ilDB->dropTableColumn('il_dcl_viewdefinition', 'id');
}
$ilDB->dropSequence('il_dcl_viewdefinition');
?>
<#3700>
<?php
if(!$ilDB->tableColumnExists('il_dcl_data', 'edit_by_owner'))
{
	$ilDB->addTableColumn(
		'il_dcl_data',
		'edit_by_owner',
		array(
			'type'    => 'integer',
			'length'  => 1,
		)
	);
	
}
?>
<#3701>
<?php
if(!$ilDB->tableColumnExists('cmi_node', 'additional_tables'))
{
	$ilDB->addTableColumn('cmi_node', 'additional_tables', array(
															'type'    => 'integer',
															'length'  => 1,
															'notnull' => true,
															'default' => 0
													   ));
	$ilDB->query("UPDATE cmi_node SET additional_tables = 15");
}
?>
<#3702>
<?php
if($ilDB->tableColumnExists('cmi_node', 'cp_node_id'))
{
	$ilDB->query("DELETE from cmi_node where cp_node_id is null");
	$reverse = $ilDB->db->loadModule('Reverse');
	$def = $reverse->getTableFieldDefinition("cmi_node", "cp_node_id");
	if($def[0]['notnull'] == false)
	{
		$ilDB->modifyTableColumn('cmi_node', 'cp_node_id', array(
															'type'    => 'integer',
															'length'  => 4,
															'notnull' => true,
															'default' => 0
													   ));
	}
}
?>
<#3703>
<?php
if($ilDB->tableColumnExists('cmi_node', 'user_id'))
{
	$ilDB->query("DELETE from cmi_node where user_id is null");
	$reverse = $ilDB->db->loadModule('Reverse');
	$def = $reverse->getTableFieldDefinition("cmi_node", "user_id");
	if($def[0]['notnull'] == false)
	{
		$ilDB->modifyTableColumn('cmi_node', 'user_id', array(
															'type'    => 'integer',
															'length'  => 4,
															'notnull' => true,
															'default' => 0
													   ));
	}
}
?>
<#3704>
<?php

if (!$ilDB->tableExists('rcat_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		)
	);
	$ilDB->createTable('rcat_settings', $fields);
	$ilDB->addPrimaryKey('rcat_settings', array('obj_id'));
}

?>
<#3705>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rcat_type_id = ilDBUpdateNewObjectType::addNewType('rcat', 'Remote Category Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rcat_type_id, $rbac_ops);

?>
<#3706>
<?php

if (!$ilDB->tableExists('rwik_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		),
		'availability_type' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
	);
	$ilDB->createTable('rwik_settings', $fields);
	$ilDB->addPrimaryKey('rwik_settings', array('obj_id'));
}

?>
<#3707>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rwik_type_id = ilDBUpdateNewObjectType::addNewType('rwik', 'Remote Wiki Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rwik_type_id, $rbac_ops);

?>
<#3708>
<?php

if (!$ilDB->tableExists('rlm_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		),
		'availability_type' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
	);
	$ilDB->createTable('rlm_settings', $fields);
	$ilDB->addPrimaryKey('rlm_settings', array('obj_id'));
}

?>
<#3709>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rlm_type_id = ilDBUpdateNewObjectType::addNewType('rlm', 'Remote Learning Module Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rlm_type_id, $rbac_ops);

?>
<#3710>
<?php

if (!$ilDB->tableExists('rglo_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		),
		'availability_type' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
	);
	$ilDB->createTable('rglo_settings', $fields);
	$ilDB->addPrimaryKey('rglo_settings', array('obj_id'));
}

?>
<#3711>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rglo_type_id = ilDBUpdateNewObjectType::addNewType('rglo', 'Remote Glossary Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rglo_type_id, $rbac_ops);

?>
<#3712>
	<?php
	if(!$ilDB->tableColumnExists('il_dcl_record', 'last_edit_by'))
	{
		$ilDB->addTableColumn(
			'il_dcl_record',
			'last_edit_by',
			array(
				'type'    => 'integer',
				'length'  => 4,
			)
		);

	}
	?>
<#3713>
<?php
if(!$ilDB->tableColumnExists('il_dcl_viewdefinition', 'is_set'))
{
	$ilDB->addTableColumn(
		'il_dcl_viewdefinition',
		'is_set',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 0
		)
	);

}
?>
<#3714>
<?php

if (!$ilDB->tableExists('rfil_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		),
		'version' => array(
			'type' => 'integer',
			'length' => 2,
			'notnull' => true,
			'default' => 1
		),
		'version_tstamp' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
		)
	);
	$ilDB->createTable('rfil_settings', $fields);
	$ilDB->addPrimaryKey('rfil_settings', array('obj_id'));
}

?>
<#3715>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rfil_type_id = ilDBUpdateNewObjectType::addNewType('rfil', 'Remote File Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rfil_type_id, $rbac_ops);

?>
<#3716>
<?php

if (!$ilDB->tableExists('rgrp_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		),
		'availability_type' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
		'availability_start' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
		),
		'availability_end' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
		)
	);
	$ilDB->createTable('rgrp_settings', $fields);
	$ilDB->addPrimaryKey('rgrp_settings', array('obj_id'));
}

?>
<#3717>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rgrp_type_id = ilDBUpdateNewObjectType::addNewType('rgrp', 'Remote Group Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rgrp_type_id, $rbac_ops);

?>
<#3718>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3719>
<?php
if(!$ilDB->tableColumnExists('il_dcl_table', 'blocked'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'blocked',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 0
		)
	);

}
?>
<#3720>
<?php
if(!$ilDB->tableColumnExists('il_dcl_field', 'is_unique'))
{
	$ilDB->addTableColumn(
		'il_dcl_field',
		'is_unique',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 0
		)
	);

}
?>
<#3721>
<?php
if(!$ilDB->tableColumnExists('tst_tests', 'autosave'))
{
	$ilDB->addTableColumn(
		'tst_tests',
		'autosave',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 0
		)
	);
	
	$ilDB->manipulate('UPDATE tst_tests SET autosave = ' . $ilDB->quote(0, 'integer') );

}
if(!$ilDB->tableColumnExists('tst_tests', 'autosave_ival'))
{
	$ilDB->addTableColumn(
		'tst_tests',
		'autosave_ival',
		array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default'        => 0
		)
	);
	
	$ilDB->manipulate('UPDATE tst_tests SET autosave_ival = ' . $ilDB->quote(30000, 'integer') );

}
?>
<#3722>
<?php

if (!$ilDB->tableExists('rtst_settings'))
{
	$fields = array(
		'obj_id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'mid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'organization' => array(
			'type' => 'text',
			'length' => 400,			
			'notnull' => false
		),
		'local_information' => array(
			'type' => 'text',
			'length' => 4000,
			'notnull' => false
		),
		'remote_link' => array(
			'type' => 'text',
			'length' => 400,
			'notnull' => false
		),
		'availability_type' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true,
			'default' => 0
		),
		'availability_start' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
		),
		'availability_end' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => false
		)
	);
	$ilDB->createTable('rtst_settings', $fields);
	$ilDB->addPrimaryKey('rtst_settings', array('obj_id'));
}

?>
<#3723>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$rtst_type_id = ilDBUpdateNewObjectType::addNewType('rtst', 'Remote Test Object');

$rbac_ops = array(
	ilDBUpdateNewObjectType::RBAC_OP_EDIT_PERMISSIONS,
	ilDBUpdateNewObjectType::RBAC_OP_VISIBLE,
	ilDBUpdateNewObjectType::RBAC_OP_READ,
	ilDBUpdateNewObjectType::RBAC_OP_WRITE,
	ilDBUpdateNewObjectType::RBAC_OP_DELETE
);
ilDBUpdateNewObjectType::addRBACOperations($rtst_type_id, $rbac_ops);

?>
<#3724>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3725>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$blog_type_id = ilDBUpdateNewObjectType::getObjectTypeId('blog');
$ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('contribute', 'Contribute', 'object', 3205);
if($ops_id && $blog_type_id)
{
	ilDBUpdateNewObjectType::addRBACOperation($blog_type_id, $ops_id);
}

?>
<#3726>
<?php
if(!$ilDB->tableColumnExists('il_dcl_table', 'add_perm'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'add_perm',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 1
		)
	);
}
if(!$ilDB->tableColumnExists('il_dcl_table', 'edit_perm'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'edit_perm',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 1
		)
	);
}
if(!$ilDB->tableColumnExists('il_dcl_table', 'delete_perm'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'delete_perm',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 1
		)
	);
}
if(!$ilDB->tableColumnExists('il_dcl_table', 'edit_by_owner'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'edit_by_owner',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 1
		)
	);
}
if(!$ilDB->tableColumnExists('il_dcl_table', 'limited'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'limited',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 0
		)
	);
}
if(!$ilDB->tableColumnExists('il_dcl_table', 'limit_start'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'limit_start',
		array(
			'type'    => 'timestamp',
			'notnull' => false
		)
	);
}
if(!$ilDB->tableColumnExists('il_dcl_table', 'limit_end'))
{
	$ilDB->addTableColumn(
		'il_dcl_table',
		'limit_end',
		array(
			'type'    => 'timestamp',
			'notnull' => false
		)
	);
}
?>
<#3727>
<?php
if($ilDB->tableColumnExists('il_dcl_data', 'edit_type'))
	$ilDB->dropTableColumn('il_dcl_data', 'edit_type');
if($ilDB->tableColumnExists('il_dcl_data', 'edit_start'))
	$ilDB->dropTableColumn('il_dcl_data', 'edit_start');
if($ilDB->tableColumnExists('il_dcl_data', 'edit_end'))
	$ilDB->dropTableColumn('il_dcl_data', 'edit_end');
if($ilDB->tableColumnExists('il_dcl_data', 'edit_by_owner'))
	$ilDB->dropTableColumn('il_dcl_data', 'edit_by_owner');
?>
<#3728>
<?php
if(!$ilDB->tableColumnExists('il_dcl_field', 'is_locked'))
{
	$ilDB->addTableColumn(
		'il_dcl_field',
		'is_locked',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default'        => 0
		)
	);
}
?>
<#3729>
<?php
  $ilDB->manipulateF(
    'UPDATE il_dcl_datatype_prop SET inputformat = %s WHERE title = %s',
    array('integer', 'text'),
    array(2, 'regex')
  );
?>
<#3730>
<?php
  $ilDB->manipulateF(
    'UPDATE il_dcl_datatype_prop SET inputformat = %s WHERE title = %s',
    array('integer', 'text'),
    array(1, 'table_id')
  );
?>
<#3731>
<?php
  $ins_res = $ilDB->manipulateF('INSERT INTO il_dcl_datatype_prop (id, datatype_id, title, inputformat)
      VALUES(%s,%s,%s,%s)',
    array('integer','integer', 'text','integer'),
    array(4,2, 'url',2));
?>
<#3732>
<?php
  $ilDB->manipulateF(
    'UPDATE il_dcl_datatype_prop SET inputformat = %s WHERE title = %s',
    array('integer', 'text'),
    array(4, 'url')
  );
?>
<#3733>
<?php
if($ilDB->tableColumnExists('il_dcl_table', 'blocked'))
{
	$ilDB->dropTableColumn('il_dcl_table', 'blocked');
}
?>
<#3734>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3735>
<?php
$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) VALUES (".$ilDB->quote(7, "integer").", ".$ilDB->quote("rating", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(0, "integer").")");
?>
<#3736>
<?php
if(!$ilDB->tableColumnExists('il_poll', 'period'))
{
	$ilDB->addTableColumn(
		'il_poll',
		'period',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => true,
			'default' => 0
		)
	);
	$ilDB->addTableColumn(
		'il_poll',
		'period_begin',
		array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		)
	);
	$ilDB->addTableColumn(
		'il_poll',
		'period_end',
		array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => false,
			'default' => 0
		)
	);
}
?>
<#3737>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3738>
<?php
$ilDB->manipulateF("INSERT INTO il_dcl_datatype_prop (id,datatype_id,title,inputformat) VALUES ".
		   " (%s,%s,%s,%s)",
        	   array("integer", "integer", "text", "integer"),
                   array(5, 2, "text_area", 4));
?>
<#3739>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3740>
<?php
$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
        " VALUES (".
        $ilDB->quote(8, "integer").", ".$ilDB->quote("ILIAS_reference", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(2, "integer").
        ")");
?>
<#3741>
<?php
if(!$ilDB->tableColumnExists('il_blog', 'approval'))
{
	$ilDB->addTableColumn(
		'il_blog',
		'approval',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => false,
			'default' => 0
		)
	);
	$ilDB->addTableColumn(
		'il_blog_posting',
		'approved',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'notnull' => false,
			'default' => 0
		)
	);
}
?>
<#3742>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3743>
<?php

include_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

$sess_ops_id = ilDBUpdateNewObjectType::getCustomRBACOperationId('create_sess');
if($sess_ops_id)
{
	$fold_type_id = ilDBUpdateNewObjectType::getObjectTypeId('fold');
	if($fold_type_id)
	{
		ilDBUpdateNewObjectType::addRBACOperation($fold_type_id, $sess_ops_id);
	}
	$grp_type_id = ilDBUpdateNewObjectType::getObjectTypeId('grp');
	if($grp_type_id)
	{
		ilDBUpdateNewObjectType::addRBACOperation($grp_type_id, $sess_ops_id);
	}
}

?>
<#3744>
<?php
if(!$ilDB->tableColumnExists('booking_object', 'info_file'))
{
	$ilDB->addTableColumn(
		'booking_object',
		'info_file',
		array(
			'type'    => 'text',
			'length'  => 500,
			'notnull' => false
		)
	);
	$ilDB->addTableColumn(
		'booking_object',
		'post_text',
		array(
			'type'    => 'text',
			'length'  => 4000,
			'notnull' => false
		)
	);
	$ilDB->addTableColumn(
		'booking_object',
		'post_file',
		array(
			'type'    => 'text',
			'length'  => 500,
			'notnull' => false
		)
	);
}
?>
<#3745>
<?php
$ilDB->manipulateF("INSERT INTO il_dcl_datatype_prop (id,datatype_id,title,inputformat) VALUES ".
		   " (%s,%s,%s,%s)",
        	   array("integer", "integer", "text", "integer"),
                   array(6, 3, "reference_link", 4));
?>
<#3746>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#3747>
<?php
$ilDB->manipulate("INSERT INTO il_dcl_datatype (id, title, ildb_type, storage_location) ".
		" VALUES (".
		$ilDB->quote(9, "integer").", ".$ilDB->quote("mob", "text").", ".$ilDB->quote("integer", "text").", ".$ilDB->quote(2, "text").
		")");
		
?>
<#3748>
<?php
$ilDB->manipulateF("INSERT INTO il_dcl_datatype_prop (id,datatype_id,title,inputformat) VALUES ".
		   " (%s,%s,%s,%s)",
        	   array("integer", "integer", "text", "integer"),
                   array(7, 9, "width", 1));

$ilDB->manipulateF("INSERT INTO il_dcl_datatype_prop (id,datatype_id,title,inputformat) VALUES ".
		   " (%s,%s,%s,%s)",
        	   array("integer", "integer", "text", "integer"),
                   array(8, 9, "height", 1));		
?>
<#3749>
<?php
$ilDB->manipulateF("INSERT INTO il_dcl_datatype_prop (id,datatype_id,title,inputformat) VALUES ".
		   " (%s,%s,%s,%s)",
        	   array("integer", "integer", "text", "integer"),
                   array(9, 8, "learning_progress", 4));
?>
<#3750>
<?php
if(!$ilDB->tableColumnExists('il_rating', 'tstamp'))
{
	$ilDB->addTableColumn(
		'il_rating',
		'tstamp',
		array(
			'type'    => 'integer',
			'length'  => 4,
			'notnull' => false
		)
	);
}
?>
<#3751>
<?php
$setting = new ilSetting();
$setting->set('obj_dis_creation_icrs', 1);
$setting->set('obj_dis_creation_icla', 1);
?>
<#3752>
<?php
$ilDB->manipulateF("INSERT INTO il_dcl_datatype_prop (id,datatype_id,title,inputformat) VALUES ".
		   " (%s,%s,%s,%s)",
        	   array("integer", "integer", "text", "integer"),
                   array(10, 8, "ILIAS_reference_link", 4));
?>
<#3753>
<?php
$setting = new ilSetting();
$ade =  $setting->get("admin_email");
$fbr = $setting->get("feedback_recipient");
if(trim($ade) && !trim($fbr))
{
	$setting->set("feedback_recipient", $ade);
}
?>
<#3754>
<?php
	// see 3619
	$ts_now = time();
	$ts_latest = mktime(23,55,00,date('n',time()),date('j',time()),date('Y',time()));

	// all limited course objects with ref_id and parent ref_id
	$query = "SELECT t.child,t.parent,c.startdate,c.enddate".
		" FROM svy_svy c".
		" JOIN object_reference r ON (r.obj_id = c.obj_fi)".
		" JOIN tree t ON (r.ref_id = t.child)".
		" LEFT JOIN crs_items i ON (i.obj_id = r.ref_id)".
		" WHERE i.timing_type IS NULL";
	$set = $ilDB->query($query);
	while($row = $ilDB->fetchAssoc($set))
	{				
		if($row["startdate"] || $row["enddate"])
		{									
			$ts_start = time();
			if(preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $row["startdate"], $d_parts))
			{			
				$ts_start = mktime(
					isset($d_parts[4]) ? $d_parts[4] : 0, 
					isset($d_parts[5]) ? $d_parts[5] : 0,
					isset($d_parts[6]) ? $d_parts[6] : 0,
					$d_parts[2],
					$d_parts[3],
					$d_parts[1]);
			}
			$ts_end = mktime(0, 0, 1, 1, 1, date("Y")+3);
			if(preg_match("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $row["enddate"], $d_parts))
			{			
				$ts_end = mktime(
					isset($d_parts[4]) ? $d_parts[4] : 0, 
					isset($d_parts[5]) ? $d_parts[5] : 0,
					isset($d_parts[6]) ? $d_parts[6] : 0,
					$d_parts[2],
					$d_parts[3],
					$d_parts[1]);
			}
			
			$query = "INSERT INTO crs_items (parent_id,obj_id,timing_type,timing_start,".
				"timing_end,suggestion_start,suggestion_end,changeable,earliest_start,".
				"latest_end,visible,position) VALUES (".
				$ilDB->quote($row["parent"],'integer').",".
				$ilDB->quote($row["child"],'integer').",".
				$ilDB->quote(0,'integer').",".
				$ilDB->quote($ts_start,'integer').",".
				$ilDB->quote($ts_end,'integer').",".
				$ilDB->quote($ts_now,'integer').",".
				$ilDB->quote($ts_now,'integer').",".
				$ilDB->quote(0,'integer').",".
				$ilDB->quote($ts_now,'integer').", ".
				$ilDB->quote($ts_latest,'integer').", ".
				$ilDB->quote(1,'integer').", ".
				$ilDB->quote(0,'integer').")";		
			$ilDB->manipulate($query);				
		}
	}
?>
<#3755>
<?php
	$ilDB->addPrimaryKey('qpl_fb_cloze', array('feedback_id'));
?>
<#3756>
<?php
	$ilDB->addPrimaryKey('qpl_fb_errortext', array('feedback_id'));
?>
<#3757>
<?php
	$ilDB->addPrimaryKey('qpl_fb_matching', array('feedback_id'));
?>
<#3758>
<?php
	$ilDB->addPrimaryKey('qpl_fb_essay', array('feedback_id'));
?>

<#3759>
<?php

	if(!$ilDB->tableColumnExists('usr_data', 'inactivation_date'))
	{
		$ilDB->addTableColumn('usr_data', 'inactivation_date', array(
			'type' => 'timestamp',
			'notnull' => false,
			'default' => null
		));
	}
	else
	{
		// if field does already exist, this is the awd installation,
		// so turn stored configuration to new inactivation cron

		$settings = array(
			'cron_inactive_user_delete',
			'cron_inactive_user_delete_interval',
			'cron_inactive_user_delete_include_roles',
			'cron_inactive_user_delete_period',
			'cron_inactive_user_delete_last_run'
		);

		$_keyword_IN_keywords = $ilDB->in('keyword', $settings, false, 'text');

		$res = $ilDB->query("
			SELECT keyword, value
			FROM settings
			WHERE $_keyword_IN_keywords
		");

		while( $row = $ilDB->fetchAssoc($res) )
		{
			$settingName = $row['keyword'];
			$settingValue = is_null($row['value']) ? 0 : $row['value'];

			$settingName = str_replace('cron_inactive_', 'cron_inactivated_', $settingName);

			$ilDB->insert('settings', array(
				'module' => array('text', 'common'),
				'keyword' => array('text', $settingName),
				'value' => array('text', $settingValue)
			));
		}

		$ilDB->manipulate("DELETE FROM settings WHERE $_keyword_IN_keywords");
	}

?>
<#3760>
<?php

	// map wsp ids to object ids
	$ntfmap = array();
	$set = $ilDB->query("SELECT ntf.id,orw.obj_id".
		" FROM notification ntf".
		" JOIN object_reference_ws orw ON (ntf.id = orw.wsp_id)".
		" WHERE ntf.type = ".$ilDB->quote(4, "integer"));
	while($row = $ilDB->fetchAssoc($set))
	{
		$ntfmap[$row["id"]] = $row["obj_id"];		
	}
	
	if(sizeof($ntfmap))
	{
		// remove existing object entries (just to make sure, there should be none)
		 $ilDB->manipulate("DELETE FROM notification".		
			" WHERE type = ".$ilDB->quote(4, "integer").
			" AND ".$ilDB->in("id", array_values($ntfmap), "", "integer"));
		 
		 // convert wsp_id entries to obj_id entries
		 foreach($ntfmap as $ntf_wsp_id => $ntf_obj_id)
		 {
			 $ilDB->manipulate("UPDATE notification".
				" SET id = ".$ilDB->quote($ntf_obj_id, "integer").
				" WHERE id = ".$ilDB->quote($ntf_wsp_id, "integer").
				" AND type = ".$ilDB->quote(4, "integer"));						 
		 }
	}
	
?>