<?php
	// ********************************************
	// Script de connexion WEB
	// ********************************************
	include_once("$PATH/class/mysql.class.inc.php");
	
	// Si on utilise la variable $HOST, ca pose problème avec la recherche de dispo et la checkbox pour le .host (ce qui génère un $_POST["HOST"]
	
	
	$HOSTT["DEFAULT"]=array("dbconnect");
	
	if(!isset($arrHOST))
		global $arrHOST;

	if(!isset($arrHOST) || empty($arrHOST) || !isset($HOSTT[$arrHOST]))
		$HOST_DB=$HOSTT["DEFAULT"];
	else
		$HOST_DB=$HOSTT[$arrHOST];
		
	// paramétres de connexion
	$USER_DB="";
	$PASS_DB="";
	$DATABASE="db_pete-les-plombs";
	
	$LOG_DB_REQUESTS=false;
	
	// Création d'une connexion par défaut
	$db=new Mysql($HOST_DB, $USER_DB, $PASS_DB,$DATABASE);
	if(!$db->Connect())
		echo "Mysql Error $DATABASE: Connexion impossible";
	
	if(isset($DEUX_CONNECTIONS) && $DEUX_CONNECTIONS)
	{
		// Création d'une seconde connexion
		$db2=new Mysql($HOST_DB, $USER_DB, $PASS_DB,$DATABASE);
		if(!$db2->Connect())
			echo "Mysql Error $DATABASE: Connexion 2 impossible";
	}
?>
