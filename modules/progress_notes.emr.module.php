<?php
 // $Id$
 // note: progress notes module for patient management
 // lic : GPL, v2

LoadObjectDependency('FreeMED.EMRModule');

class ProgressNotes extends EMRModule {

	var $MODULE_NAME = "Progress Notes";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.2.1";
	var $MODULE_DESCRIPTION = "
		FreeMED Progress Notes allow physicians and
		providers to track patient activity through
		SOAPIER style notes.
	";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.6.0';

	var $record_name   = "Progress Notes";
	var $table_name    = "pnotes";
	var $patient_field = "pnotespat";

	function ProgressNotes () {
		// Table description
		$this->table_definition = array (
			'pnotesdt' => SQL_DATE,
			'pnotesdtadd' => SQL_DATE,
			'pnotesdtmod' => SQL_DATE,
			'pnotespat' => SQL_INT_UNSIGNED(0),
			'pnotesdescrip' => SQL_VARCHAR(100),
			'pnotesdoc' => SQL_INT_UNSIGNED(0),
			'pnoteseoc' => SQL_INT_UNSIGNED(0),
			'pnotes_S' => SQL_TEXT,
			'pnotes_O' => SQL_TEXT,
			'pnotes_A' => SQL_TEXT,
			'pnotes_P' => SQL_TEXT,
			'pnotes_I' => SQL_TEXT,
			'pnotes_E' => SQL_TEXT,
			'pnotes_R' => SQL_TEXT,
			'iso' => SQL_VARCHAR(15),
			'locked' => SQL_INT_UNSIGNED(0),
			'id' => SQL_SERIAL
		);
	
		// Define variables for EMR summary
		$this->summary_vars = array (
			__("Date")        =>	"pnotesdt",
			__("Description") =>	"pnotesdescrip"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_LOCK;

		// Set associations
		$this->_SetAssociation('EpisodeOfCare');
		$this->_SetMetaInformation('EpisodeOfCareVar', 'pnoteseoc');

		// Call parent constructor
		$this->EMRModule();
	} // end constructor ProgressNotes

	function add () { $this->form(); }
	function mod () { $this->form(); }

	function form () {
		global $display_buffer, $sql, $pnoteseoc;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		$book = CreateObject('PHP.notebook',
				array ("id", "module", "patient", "action", "return"),
				NOTEBOOK_COMMON_BAR | NOTEBOOK_STRETCH, 4);

		switch ($action) {
			case "add": case "addform":
			$book->set_submit_name(__("Add"));
			break;

			case "mod": case "modform":
			$book->set_submit_name(__("Modify"));
			break;
		}
     
		if (!$book->been_here()) {
      switch ($action) { // internal switch
        case "addform":
 	// check if we are a physician
         if ($this->this_user->isPhysician()) {
		global $pnotesdoc;
 		$pnotesdoc = $this->this_user->getPhysician(); // if so, set as default
	}
         $pnotesdt     = $cur_date;
         break; // end addform
        case "modform":
         //while(list($k,$v)=each($this->variables)) { global ${$v}; }

         if (($id<1) OR (strlen($id)<1)) {
           $page_title = _($this->record_name)." :: ".__("ERROR");
           $display_buffer .= "
             ".__("You must select a patient.")."
           ";
           template_display();
         }

         $r = freemed::get_link_rec ($id, $this->table_name);

	 if ($r['locked'] > 0) {
		$display_buffer .= "
		<div ALIGN=\"CENTER\">
		".__("This record is locked, and cannot be modified.")."
		</div>

		<p/>
		
		<div ALIGN=\"CENTER\">
		".
		(($return == "manage") ?
		"<a href=\"manage.php?id=$patient\">".__("Manage Patient")."</a>" :
		"<a href=\"module_loader.php?module=".get_class($this)."\">".
			__("back")."</a>" )
		."\n</div>\n";
		return false;
	 }
	 
         foreach ($r AS $k => $v) {
           global ${$k}; ${$k} = stripslashes($v);
         }
  	 	 extract ($r);
         break; // end modform
      } // end internal switch
     } // end checking if been here

	// Check for progress notes templates addon module
	if (check_module("ProgressNotesTemplates") and ($action=='addform')) {
		// Create picklist widget
		$pnt_array = array (
			__("Progress Notes Template") =>
			module_function(
				'ProgressNotesTemplates', 
				'picklist', 
				array('pnt', $book->formname)
			)
		);

		// Check for used status
		module_function(
			'ProgressNotesTemplates',
			'retrieve',
			array('pnt')
		);
	} else {
		$pnt_array = array ("" => "");
	}

     // Check episode of care dependency
     if(check_module("EpisodeOfCare")) {
       // Actual piece
       global $pnoteseoc;
	$pnoteseoc = sql_squash($pnoteseoc); // for multiple choice (HACK)
       $related_episode_array = array (
         __("Related Episode(s)") =>
	 module_function('EpisodeOfCare','widget',array('pnoteseoc', $patient))
        );
     } else {
        // Put in blank array instead
	$related_episode_array = array ("" => "");
     }
     $book->add_page (
       __("Basic Information"),
       array ("pnotesdoc", "pnotesdescrip", "pnoteseoc", date_vars("pnotesdt")),
       "<input TYPE=\"HIDDEN\" NAME=\"pnt_used\" VALUE=\"\"/>\n".
       html_form::form_table (
        array_merge (
	$pnt_array,
        array (
	 __("Provider") =>
	   freemed_display_selectbox (
            $sql->query ("SELECT * FROM physician ".
	    	"WHERE phyref != 'yes' AND phylname != '' ".
		"ORDER BY phylname,phyfname"),
	    "#phylname#, #phyfname#",
	    "pnotesdoc"
	   ),
	   
         __("Description") =>
	html_form::text_widget("pnotesdescrip", 25, 100)
	),
	$related_episode_array,
	array (
         __("Date") => fm_date_entry("pnotesdt") 
	 )
        ) // end array_merge	
        )
      ); 

     $book->add_page (
       __("<u>S</u>ubjective"),
       array ("pnotes_S"),
       html_form::form_table (
        array (
          __("<u>S</u>ubjective") =>
          "<TEXTAREA NAME=\"pnotes_S\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_S)."</TEXTAREA>"
        )
       )
     );

     $book->add_page (
       __("<u>O</u>bjective"),
       array ("pnotes_O"),
       html_form::form_table (
        array (
          __("<u>O</u>bjective") =>
          "<TEXTAREA NAME=\"pnotes_O\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_O)."</TEXTAREA>"
        )
       )
     );

     $book->add_page (
       __("<U>A</U>ssessment"),
       array ("pnotes_A"),
       html_form::form_table (
        array (
          __("<U>A</U>ssessment") =>
          "<TEXTAREA NAME=\"pnotes_A\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_A)."</TEXTAREA>"
        )
       )
     );

     $book->add_page (
       __("<U>P</U>lan"),
       array ("pnotes_P"),
       html_form::form_table (
        array (
          __("<U>P</U>lan") =>
          "<TEXTAREA NAME=\"pnotes_P\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_P)."</TEXTAREA>"
        )
       )
     );

     $book->add_page (
       __("<U>I</U>nterval"),
       array ("pnotes_I"),
       html_form::form_table (
        array (
          __("<U>I</U>nterval") =>
          "<TEXTAREA NAME=\"pnotes_I\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_I)."</TEXTAREA>"
        )
       )
     );

     $book->add_page (
       __("<U>E</U>ducation"),
       array ("pnotes_E"),
       html_form::form_table (
        array (
          __("<U>E</U>ducation") =>
          "<TEXTAREA NAME=\"pnotes_E\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_E)."</TEXTAREA>"
        )
       )
     );

     $book->add_page (
       __("P<U>R</U>escription"),
       array ("pnotes_R"),
       html_form::form_table (
        array (
          __("P<U>R</U>escription") =>
          "<TEXTAREA NAME=\"pnotes_R\" ROWS=8 COLS=45
         WRAP=VIRTUAL>".prepare($pnotes_R)."</TEXTAREA>"
        )
       )
     );

	// Handle cancel action
	if ($book->is_cancelled()) {
		if ($return=='manage') {
			Header("Location: manage.php?id=".urlencode($patient));
		} else {
			Header("Location: ".$this->page_name."?".
				"module=".$this->MODULE_CLASS."&".
				"patient=".$patient);
		}
		die("");
	}

     if (!$book->is_done()) {
      $display_buffer .= $book->display();
     } else {
       switch ($action) {
        case "addform": case "add":
         $display_buffer .= "
           <div ALIGN=\"CENTER\"><b>".__("Adding")." ... </b>
         ";
           // preparation of values
         $pnotesdtadd = $cur_date;
         $pnotesdtmod = $cur_date;

           // actual addition
	global $patient, $locked, $__ISO_SET__, $id;
	$query = $sql->insert_query (
		$this->table_name,
		array (
			"pnotespat"      => $patient,
			"pnoteseoc",
			"pnotesdoc",
			"pnotesdt"       => fm_date_assemble("pnotesdt"),
			"pnotesdescrip",
			"pnotesdtadd"    => date("Y-m-d"),
			"pnotesdtmod"    => date("Y-m-d"),
			"pnotes_S",
			"pnotes_O",
			"pnotes_A",
			"pnotes_P",
			"pnotes_I",
			"pnotes_E",
			"pnotes_R",
			"locked"         => $locked,
			"iso"            => $__ISO_SET__
		)
	);
         break;

	case "modform": case "mod":
         $display_buffer .= "
           <div ALIGN=\"CENTER\"><b>".__("Modifying")." ... </b>
         ";
	global $patient, $__ISO_SET__, $locked, $id;
	$query = $sql->update_query (
		$this->table_name,
		array (
			"pnotespat"      => $patient,
			"pnoteseoc",
			"pnotesdoc",
			"pnotesdt"       => fm_date_assemble("pnotesdt"),
			"pnotesdescrip",
			"pnotesdtmod"    => date("Y-m-d"),
			"pnotes_S",
			"pnotes_O",
			"pnotes_A",
			"pnotes_P",
			"pnotes_I",
			"pnotes_E",
			"pnotes_R",
			"locked"         => $locked,
			"iso"            => $__ISO_SET__
		),
		array ( "id" => $id )
	);
	 break;
       } // end inner switch
       // now actually send the query
       $result = $sql->query ($query);
       if ($debug) $display_buffer .= "(query = '$query') ";
       if ($result)
         $display_buffer .= " <b> ".__("done").". </b>\n";
       else
         $display_buffer .= " <b> <font COLOR=\"#ff0000\">".__("ERROR")."</font> </b>\n";
       $display_buffer .= "
        </div>
        <p/>
         <div ALIGN=\"CENTER\"><a HREF=\"manage.php?id=$patient\"
          >".__("Manage Patient")."</a>
         <b>|</b>
         <a HREF=\"$this->page_name?module=$module&patient=$patient\"
          >".__($this->record_name)."</a>
	  ";
       if ($action=="mod" OR $action=="modform")
         $display_buffer .= "
	 <b>|</b>
	 <a HREF=\"$this->page_name?module=$module&patient=$patient&action=view&id=$id\"
	  >".__("View $this->record_name")."</a>
	 ";
       $display_buffer .= "
         </div>
         <p/>
         ";

	 // Handle returning to patient management screen after add
	 global $refresh;
	 if ($GLOBALS['return'] == 'manage') {
		$refresh = 'manage.php?id='.urlencode($patient);
	 }
     } // end if is done


	} // end of function ProgressNotes->form()

	function display () {
		global $display_buffer;

		// Tell FreeMED not to display a template
		$GLOBALS['__freemed']['no_template_display'] = true;
		
		foreach ($GLOBALS AS $k => $v) global $$k;
     if (($id<1) OR (strlen($id)<1)) {
       $display_buffer .= "
         ".__("Specify Notes to Display")."
         <p/>
         <div ALIGN=\"CENTER\">
	 <a HREF=\"$this->page_name?module=$module&patient=$patient\"
          >".__("back")."</a> |
          <a HREF=\"manage.php?id=$patient\"
          >".__("Manage Patient")."</a>
         </div>
       ";
       template_display();
     }
      // if it is legit, grab the data
     $r = freemed::get_link_rec ($id, "pnotes");
     if (is_array($r)) extract ($r);
     $pnotesdt_formatted = substr ($pnotesdt, 0, 4). "-".
                           substr ($pnotesdt, 5, 2). "-".
                           substr ($pnotesdt, 8, 2);
     $pnotespat = $r ["pnotespat"];
     $pnoteseoc = sql_expand ($r["pnoteseoc"]);

     $this->this_patient = CreateObject('FreeMED.Patient', $pnotespat);

     $display_buffer .= "
       <p/>
       ".template::link_bar(array(
        __("Progress Notes") =>
       $this->page_name."?module=$module&patient=$pnotespat",
        __("Manage Patient") =>
       "manage.php?id=$pnotespat",
	__("Select Patient") =>
        "patient.php",
	( freemed::user_flag(USER_DATABASE) ? __("Modify") : "" ) =>
        $this->page_name."?module=$module&patient=$patient&id=$id&action=modform"
       ))."
       <p/>

       <CENTER>
        <B>Relevant Date : </B>
         $pnotesdt_formatted
       </CENTER>
       <P>
     ";
     // Check for EOC stuff
     if (count($pnoteseoc)>0 and is_array($pnoteseoc) and check_module("episodeOfCare")) {
      $display_buffer .= "
       <CENTER>
        <B>".__("Related Episode(s)")."</B>
        <BR>
      ";
      for ($i=0;$i<count($pnoteseoc);$i++) {
        if ($pnoteseoc[$i] != -1) {
          $e_r     = freemed::get_link_rec ($pnoteseoc[$i]+0, "eoc"); 
          $e_id    = $e_r["id"];
          $e_desc  = $e_r["eocdescrip"];
          $e_first = $e_r["eocstartdate"];
          $e_last  = $e_r["eocdtlastsimilar"];
          $display_buffer .= "
           <A HREF=\"module_loader.php?module=episodeOfCare&patient=$patient&".
  	   "action=manage&id=$e_id\"
           >$e_desc / $e_first to $e_last</A><BR>
          ";
	} else {
	  $episodes = $sql->query (
	    "SELECT * FROM eoc WHERE eocpatient='".addslashes($patient)."'" );
	  while ($epi = $sql->fetch_array ($episodes)) {
            $e_id    = $epi["id"];
            $e_desc  = $epi["eocdescrip"];
            $e_first = $epi["eocstartdate"];
            $e_last  = $epi["eocdtlastsimilar"];
            $display_buffer .= "
           <A HREF=\"module_loader.php?module=episodeOfCare&patient=$patient&".
  	     "action=manage&id=$e_id\"
             >$e_desc / $e_first to $e_last</A><BR>
            ";
	  } // end fetching
	} // check if not "ALL"
      } // end looping for all EOCs
      $display_buffer .= "
       </CENTER>
      ";
     } // end checking for EOC stuff
     $display_buffer .= "<CENTER>\n";
     if (!empty($pnotes_S)) $display_buffer .= "
       <TABLE BGCOLOR=\"#ffffff\" BORDER=1><TR BGCOLOR=\"$darker_bgcolor\">
       <TD ALIGN=\"CENTER\"><B>".__("<u>S</u>ubjective")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_S)))."
       </TD></TR></TABLE>
       ";
      if (!empty($pnotes_O)) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("<U>O</U>bjective")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_O)))."
       </TD></TR></TABLE>
       ";
      if (!empty($pnotes_A)) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("<U>A</U>ssessment")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_A)))."
       </TD></TR></TABLE>
       ";
      if (!empty($pnotes_P)) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><CENTER><FONT COLOR=#ffffff>
        <B>".__("<u>P</u>lan")."</B></FONT></CENTER></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_P)))."
       </TD></TR></TABLE>
       ";
      if (!empty($pnotes_I)) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("<u>I</u>nterval")."</B></TD></TR>
       <TR BGCOLOR=\"#ffffff\"><TD>
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_I)))."
       </TD></TR></TABLE>
       ";
      if (!empty($pnotes_E)) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("<u>E</u>ducation")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
           ".prepare($pnotes_E)."
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_E)))."
       </TD></TR></TABLE> 
       ";
      if (!empty($pnotes_R)) $display_buffer .= "
      <TABLE BGCOLOR=#ffffff BORDER=1><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("P<u>R</u>escription")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
           ".stripslashes(str_replace("\n", "<BR>", htmlentities($pnotes_R)))."
       </TD></TR></TABLE>
      ";
        // back to your regularly sceduled program...
      $display_buffer .= "
       <p/>
       ".template::link_bar(array(
        __("Progress Notes") =>
       $this->page_name."?module=$module&patient=$pnotespat",
        __("Manage Patient") =>
       "manage.php?id=$pnotespat",
	__("Select Patient") =>
        "patient.php",
	( freemed::user_flag(USER_DATABASE) ? __("Modify") : "" ) =>
        $this->page_name."?module=$module&patient=$patient&id=$id&action=modform"
       ))."
       <p/>
     ";
	} // end of case display

	function view ($condition = false) {
		global $display_buffer;
		global $patient, $action;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		// Check for "view" action (actually display)
		if ($action=="view") {
			$this->display();
			return NULL;
		}

		$query = "SELECT * FROM ".$this->table_name." ".
			"WHERE (pnotespat='".addslashes($patient)."') ".
			freemed::itemlist_conditions(false)." ".
			( $condition ? 'AND '.$condition : '' )." ".
			"ORDER BY pnotesdt";
		$result = $sql->query ($query);

		$display_buffer .= freemed_display_itemlist(
			$result,
			$this->page_name,
			array (
				__("Date")        => "pnotesdt",
				__("Description") => "pnotesdescrip"
			), // array
			array (
				"",
				__("NO DESCRIPTION")
			),
			NULL, NULL, NULL,
			ITEMLIST_MOD | ITEMLIST_VIEW | ITEMLIST_DEL | ITEMLIST_LOCK
		);
		$display_buffer .= "\n<p/>\n";
	} // end function ProgressNotes->view()

} // end of class ProgressNotes

register_module ("ProgressNotes");

?>
