<?php
session_start();

$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'test';

if (!isset($_SESSION['id'])) {
	exit('No user provided');
	//$_SESSION['id'] = '12345';
}

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if ( mysqli_connect_errno()) {
	exit('Failed to connect to MySQL:' . mysqli_connect_error());
}

$combined = array();
$plans = array();
$catalogs = array();

// Hit the database tables to get the Plan information
if ($stmt = $con->prepare('SELECT iaj_user.name, iaj_plan.plan_id, iaj_plan.plan_name, iaj_plan.catalog, iaj_plan.default_plan FROM iaj_user, iaj_plan WHERE iaj_user.ID = iaj_plan.user_id AND iaj_user.ID = ?')) {
	$stmt->bind_param('s', $_SESSION['id']);
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows > 0) {
		$stmt->bind_result($userName, $planID, $planName, $planCatalog, $planDefault);
		while ($row = $stmt->fetch()) {
			$plans[$planID] = array("name"=>$planName,"student"=>$userName,"catalog"=>$planCatalog,"default"=>$planDefault,"courses"=>array(),"major"=>"TEMP_MAJOR");
			
			if ($courseStmt = $con->prepare('SELECT course_id, year, term FROM iaj_plan_courses WHERE plan_id = ?')) {
				$courseStmt->bind_param('s', $planID);
				$courseStmt->execute();
				$courseStmt->store_result();
				while ($courseRow = $courseStmt->fetch()) {
					$courseStmt->bind_result($courseID, $courseYear, $courseTerm);
					$plans[$planID]["courses"][$courseID] = array("id"=>$courseID,"year"=>$courseYear,"term"=>$courseTerm);
				}
			}
		}
	}
	$combined["plans"] = $plans;
}

// Hit the database tables to get the Catalog information
if ($stmt = $con->prepare('SELECT iaj_catalog.year FROM iaj_catalog')) {
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows > 0) {
		$stmt->bind_result($catalog);
		while ($row = $stmt->fetch()) {
			$catalogs[$catalog] = array("year"=>$catalog,"courses"=>array());
			
			if ($courseStmt = $con->prepare('SELECT iaj_course.course_id, name, description, credits FROM iaj_requirements, iaj_course WHERE iaj_requirements.year = ?')) {
				$courseStmt->bind_param('s', $catalog);
				$courseStmt->execute();
				$courseStmt->store_result();
				while ($courseRow = $courseStmt->fetch()) {
					$courseStmt->bind_result($courseID, $courseName, $courseDescription, $courseCredits);
					if ($courseID != null) {
						$catalogs[$catalog]["courses"][$courseID] = array("id"=>$courseID,"name"=>$courseName,"description"=>$courseDescription,"credits"=>$courseCredits);
					}
				}
			}
		}
	}
	$combined["catalogs"] = $catalogs;
}

echo json_encode($combined);
?>
