<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;

@session_start();


//Module includes
include "./modules/Trip Planner/moduleFunctions.php";

$edit = false;
if (isset($_GET['mode']) && isset($_GET['tripPlannerRequestID'])) {
    $edit = true;
    $tripPlannerRequestID = $_GET['tripPlannerRequestID'];
}

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_submitRequest.php') || ($edit && !isOwner($connection2, $tripPlannerRequestID, $_SESSION[$guid]['gibbonPersonID']))) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {

    if ($edit) {
        $trip = getTrip($connection2, $tripPlannerRequestID);
        $tripTeachers = array();
        $tripStudents = array();
        foreach (explode(", ", $trip["people"]) as $person) {
            $person = explode(";", $person);
            if(count($person) != 2) continue;
            if ($person[1] == "Student") {
                $tripStudents[] = $person[0];
            } else {
                $tripTeachers[] = $person[0];
            }
        }
    }

    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _(($edit ? "Edit" : "Submit") . ' Trip Request') . "</div>";
    print "</div>";

    if (isset($_GET['return'])) {
        $editLink = null;
        if(isset($_GET['tripPlannerRequestID']) && !$edit) {
            $editLink = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_requestView.php&tripPlannerRequestID=" . $_GET['tripPlannerRequestID'];
        }
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    ?>

    <script type="text/javascript">
        function descReveal(id) {
            var descBlock = $("textarea[name=\"costDescription[" + id + "]\"]");
            var descLabel = $("[for=\"costDescription[" + id + "]\"]");
            descLabel.css("display", descBlock.is(":visible") ? "none" : "block");
            descBlock.css("display", descBlock.is(":visible") ? "none" : "table-cell");
        }
    </script>

    <style>
        #costName {
            float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php print "color: #999;" ?> margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px
        }

        #costValue {
            float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php print "color: #999;" ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 95px
        }

        #costDescription {
            width: 99.2%; resize:vertical; display: none; margin-top: -15px;
        }

        .borderNone {
            border:none !important;
        }

        [for^="costDescription"] { display: none; margin-top: 5px; margin-left: 0.4%; }
    </style>

    <?php

    $costBlock = Form::create("costBlock", null);

    $row = $costBlock->addRow();
        $row->addTextfield("costName")->setRequired(false)->placeholder("Cost Name");

    $row = $costBlock->addRow();
        $row->addNumber("costValue")->minimum(0)->decimalPlaces(2)->placeholder("Value" . (($_SESSION[$guid]["currency"]!="") ? " (" . $_SESSION[$guid]["currency"] . ")" : ""));

    $row = $costBlock->addRow();
        $column = $row->addColumn();
            $column->addLabel("costDescription", "Description");
            $column->addTextArea("costDescription")->setRows(2);

    $defaultRiskTemplate = getSettingByScope($connection2, "Trip Planner", "defaultRiskTemplate");
    try {
        $sqlTemplates = "SELECT tripPlannerRiskTemplateID, name, body FROM tripPlannerRiskTemplates ORDER BY name ASC";
        $resultTemplates = $connection2->prepare($sqlTemplates);
        $resultTemplates->execute();
    } catch(PDOException $e) {
    }
    $templates = array("0"=>getSettingByScope($connection2, "Trip Planner", "riskAssessmentTemplate"));
    $templateNames = array("-1" => "None", "0" => "Custom");
    while ($rowTemplate = $resultTemplates->fetch()) {
        $templates[$rowTemplate['tripPlannerRiskTemplateID']] = $rowTemplate['body'];
        $templateNames[$rowTemplate['tripPlannerRiskTemplateID']] = $rowTemplate['name'];
    }

    $highestAction2 = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_submitRequest.php', $connection2);
    try {
        if ($highestAction2 == 'Submit Request_all') {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
        } else {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Teacher' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class";
        }
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {
    }

    $classes = array();
    while ($rowSelect = $resultSelect->fetch()) {
        $classes["Class:" . $rowSelect['gibbonCourseClassID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' - '.$rowSelect['name'];
    }

    try {
        if ($highestAction2 == 'Submit Request_all') {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlSelect = 'SELECT gibbonActivityID, name FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name ASC';
        } else {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlSelect = "SELECT gibbonActivity.gibbonActivityID, gibbonActivity.name FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE role='Organiser' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY name ASC";
        }
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {
    }

    $activities = array();
    while ($rowSelect = $resultSelect->fetch()) {
        $activities["Activity:" . $rowSelect['gibbonActivityID']] = htmlPrep($rowSelect['name']);
    }

    $groups = array("By Class" => $classes, "By Activity" => $activities);
    ?>

    <script type="text/javascript">
        <?php print "var templates = " . json_encode($templates) . ";"; ?>
        $(document).ready(function(){

            $('input[id=removeButton]').parent().css("display", "inline-block");
            $('input[id=addButton]').parent().css("display", "inline-block");
            $('input[id=removeDays]').parent().css("display", "inline-block");
            $('input[id=addDays]').parent().css("display", "inline-block");

            $('tr[id=multipleRow]').each(function(){ 
                    $(this).css("display", "none");
            }); 

            $("select[name=riskAssessmentTemplates]").on('change', function(){
                var templateID = $(this).val();
                if (templateID != "" && templateID >= 0) {
                    if(confirm("Are you sure you want to use this template. Warning: This will overwrite any thing currently written.")) {
                        tinyMCE.get("riskAssessment").setContent(templates[templateID]);
                    }
                }
            });

            $("input[name=multipleDays]").on('change', function(){
                $('tr[id=multipleRow]').each(function(){ 
                    $(this).css("display", $(this).is(":visible") ? "none" : "table-row");
                }); 
                $("[for=startDate]").text($("tr[id=multipleRow]").is(":visible") ? "Start Date" : "Date").css({ 'font-weight': 'bold' });
            });

            $("input[name=allDay]").on('change', function(){
                var enabled = $(this).prop("checked");
                $('tr[id=timeRow]').each(function(){ 
                    $(this).css("display", enabled ? "none" : "table-row");
                }); 
                modifyDayList($(this), 2);
            });

            $("select[id=dayList]").on('change', function(){
                var id = $(this).find(":selected").val();
                if (isNaN(id)) {
                    $("#startDate").val("");
                    $("#endDate").val("");
                    $("#allDay").prop("checked", "").change();
                    $("#startTime").val("");
                    $("#endTime").val("");
                    $("#addDays").val("Add Days");
                } else if (id != dayID) {
                    $("#startDate").val(daysList[id][0]);
                    $("#endDate").val(daysList[id][1]);
                    $("#allDay").prop("checked", daysList[id][2]).change();
                    $("#startTime").val(daysList[id][3]);
                    $("#endTime").val(daysList[id][4]);
                    $("#addDays").val("Duplicate Days");
                }
            });

            $("input[name=startDate]").on('change', function() { modifyDayList($(this), 0); });
            $("input[name=endDate]").on('change', function() { modifyDayList($(this), 1); });
            $("input[name=startTime]").on('change', function() { modifyDayList($(this), 3); });
            $("input[name=endTime]").on('change', function() { modifyDayList($(this), 4); });

            var form = $("#requestForm");
            form.submit(function(){
                var names = ["startDate", "endDate", "allDay", "startTime", "endTime"];
                for (var i = 0; i < daysList.length; i++) {
                    if (daysList[i] != null) {
                        for (var j = 0; j < 5; j++) {
                            $("<input>").attr({
                                type: 'hidden',
                                name: "days[" + i + "][" + names[j] + "]"
                            }).val(daysList[i][j]).appendTo(form);        
                        }
                    }
                }
            });
        });

        function addClass(type) {
            var gibbonCourseClassID = document.getElementById("addStudentsByClass").value;
            if(gibbonCourseClassID != "") {
                $("#addClassDiv").load("<?php print $_SESSION[$guid]["absoluteURL"] . '/modules/Trip%20Planner/trips_submitRequestAddClassAjax.php'?>", "gibbonCourseClassID=" + gibbonCourseClassID + "&type=" + type);
            }
        }

        var dayID = 0;
        var daysList = new Array();

        function addDay() {
            var dayList = $("#dayList");
            var startDate = $("#startDate");
            var endDate = $("#endDate");
            var allDay = $("#allDay");
            var startTime = $("#startTime");
            var endTime = $("#endTime");

            if (startDate.val() == "" || endDate.val() == "" || ((startTime.val() == "" || endTime.val() == "") && !allDay.prop("checked"))) return;
            if ((new Date(startDate.val()) > new Date(endDate.val()))) {
                alert(<?php print "'" . __("Start date must be before end date.") . "'"?>);
                return;
            }
            if (!(new Date(startDate.val()) == new Date(endDate.val())) && (startTime.val() > endTime.val()) && !allDay.prop("checked")) {
                alert(<?php print "'" . __("Start time must be before end time for one day times.") . "'"?>);
                return;
            }

            daysList[dayID] = [startDate.val(), endDate.val(), allDay.prop("checked"), startTime.val(), endTime.val()];
            dayList.append($("<option>", {value: dayID, text: startDate.val() + (startDate.val() != endDate.val() ? " - " + endDate.val() : "")}));

            startDate.val("");
            endDate.val("");
            allDay.prop("checked", "").change();
            startTime.val("");
            endTime.val("");
            $("#addDays").val("Add Days");
            dayList.val("<?php print __("Add New Days")?>");
            dayID++;
        }

        function remDay() {
            if (confirm("Are you sure you want to delete these days?")) {
                var dayList = $("#dayList");
                var id = dayList.find(":selected").val();
                daysList[id] = null;
                dayList.find("option[value=" + id + "]").detach().remove();
            }
        }

        function modifyDayList(selector, index) {
            console.log(index);
            var id = $("select[id=dayList]").find(":selected").val();
            if (!isNaN(id)) {
                if (index == 0) {
                    if (selector.val()>daysList[id][1]) {
                        alert(<?php print "'" . __("Start date must be before end date.") . "'"?>);
                        selector.val(daysList[id][index]);
                        return;
                    }
                } else if (index == 1) {
                    if (selector.val()<daysList[id][0]) {
                        alert(<?php print "'" . __("End date must be after start date.") . "'"?>);
                        selector.val(daysList[id][index]);
                        return;
                    }
                } else if (index == 3) {
                    if (selector.val()>daysList[id][4] && daysList[id][0]==daysList[id][1]) {
                        alert(<?php print "'" . __("Start time must be before end time for one day times.") . "'"?>);
                        //TODO: Make this actually revert time
                        selector.val(daysList[id][index]);
                        return;
                    }
                } else if (index == 4) {
                    if (selector.val()<daysList[id][3] && daysList[id][0]==daysList[id][1]) {
                        alert(<?php print "'" . __("End time must be after start time for one day times.") . "'"?>);
                        //TODO: Make this actually revert time
                        selector.val(daysList[id][index]);
                        return;
                    }
                }
                daysList[id][index] = index == 2 ? selector.prop("checked") : selector.val();
                if(index == 0 || index == 1) {
                    $("select[id=dayList]").find(":selected").text(daysList[id][0] + (daysList[id][0] != daysList[id][1] ? " - " + daysList[id][1] : ""));
                }
            }

            console.log(daysList);
        }
    </script>

    <div id="addClassDiv"></div>

    <?php

    $teachers = array();

    try {
        $sqlTeachers = "SELECT gibbonPersonID, preferredName, surname, title, gibbonRole.category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary = gibbonRole.gibbonRoleID) WHERE gibbonRole.category='Staff' AND gibbonPerson.status='Full' ORDER BY gibbonPerson.surname, gibbonPerson.preferredName ASC";
        $resultTeachers = $connection2->prepare($sqlTeachers);
        $resultTeachers->execute();
    } catch (PDOException $e) {
    }

    while (($row = $resultTeachers->fetch()) != null) {
        $teachers[$row["gibbonPersonID"]] = formatName($row['title'], $row["preferredName"], $row["surname"], $row["category"], true, true);
    }

    $students = array();
    $studentsForm = array(); 

    try {
        $dataStudents = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlStudents = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName, name";
        $resultStudents = $connection2->prepare($sqlStudents);
        $resultStudents->execute($dataStudents);
    } catch (PDOException $e) {
    }
    while ($row = $resultStudents->fetch()) {
        $students[$row["gibbonPersonID"]] = formatName('', $row["preferredName"], $row["surname"], 'Student', true) . " - " . $row["name"];
        $studentsForm[$row["gibbonPersonID"]] = $row["name"];
    }

    print "<h3>";
        print "Request";
    print "</h3>";

    if ($edit) {
        echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Trip Planner/trips_requestView.php&tripPlannerRequestID=$tripPlannerRequestID'>".__($guid, 'View')."<img style='margin-left: 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
        echo '</div>';
    }

    $form = Form::create("requestForm", $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_submitRequestProcess.php" . ($edit ? "?mode=edit&tripPlannerRequestID=" . $tripPlannerRequestID : ""));

    $row = $form->addRow();
        $row->addHeading("Basic Information");

    $row = $form->addRow();
        $row->addLabel("title", "Title");
        $row->addTextfield("title")->setRequired(true)->setValue($edit ? $trip['title'] : '');

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel("description", "Description");
        $column->addEditor("description", $guid)->setRequired(true)->showMedia(true)->setRows(10)->setValue($edit ? $trip['description'] : '');

    $row = $form->addRow();
        $row->addLabel("location", "Location");
        $row->addTextfield("location")->setRequired(true)->setValue($edit ? $trip['location'] : '');

    $row = $form->addRow();
        $row->addHeading("Date & Time");

    $row = $form->addRow();
        $row->addLabel("multipleDays","Multiple Days");
        $row->addCheckbox("multipleDays");

    $row = $form->addRow("multipleRow");
        $row->addLabel("dayList", "Days");
        $column = $row->addColumn()->addClass("right");
            $column->addSelect("dayList")->placeholder(__("Add New Days"));
            $column->addButton("Add Days", "addDay()")->addClass("shortWidth")->setID("addDays");
            $column->addButton("Remove Days", "remDay()")->addClass("shortWidth")->setID("removeDays");

    $row = $form->addRow();
        $row->addLabel("startDate", "Date")->description($_SESSION[$guid]["i18n"]["dateFormat"]);
        $row->addDate("startDate");

    $row = $form->addRow("multipleRow");
        $row->addLabel("endDate", "End Date")->description($_SESSION[$guid]["i18n"]["dateFormat"]);
        $row->addDate("endDate");

    $row = $form->addRow();
        $row->addLabel("allDay","All Day");
        $row->addCheckbox("allDay");

    $row = $form->addRow("timeRow");
        $row->addLabel("startTime", "Start Time")->description("Format: hh:mm (24hr)");
        $row->addTime("startTime");

    $row = $form->addRow("timeRow");
        $row->addLabel("endTime", "End Time")->description("Format: hh:mm (24hr)");
        //TODO:Consider if ->chainedTo("startTime"); will work for this
        $row->addTime("endTime");

    $row = $form->addRow();
        $row->addHeading("Costs");

    $row = $form->addRow();
        $column = $row->addColumn();
            $column->addCustomBlocks("cost", $costBlock, $gibbon->session)->addBlockButton("Show/Hide", $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png", "descReveal");

    $row = $form->addRow();
        $row->addHeading("Risk Assessment & Communication");

    $row = $form->addRow();
        $row->addLabel("riskAssessmentTemplates", "Risk Assessment Templates");
        $row->addSelect("riskAssessmentTemplates")->fromArray($templateNames)->selected($defaultRiskTemplate);

    $row = $form->addRow();
        $column = $row->addColumn();
            $column->addLabel("riskAssessment", "Risk Assessment");
            $column->addEditor("riskAssessment", $guid)->setRequired(true)->showMedia(true)->setRows(25)->setValue($edit ? $trip["riskAssessment"] : $templates[$defaultRiskTemplate]);

    $row = $form->addRow();
        $column = $row->addColumn();
            $column->addLabel("letterToParents", "Letter to Parents");
            $column->addEditor("letterToParents", $guid)->showMedia(true)->setRows(25)->setValue($edit ? $trip['letterToParents'] : '');

    $row = $form->addRow();
        $row->addHeading("Participants");

    $row = $form->addRow();
        $column = $row->addColumn();
            $column->addLabel("teachers", "Teachers *");
            $column->addMultiSelect("teachers")->source()->fromArray($teachers);

    $row = $form->addRow();
        $column = $row->addColumn()->addClass("borderNone");
            $column->addLabel("students", "Students");
            $multiSelect = $column->addMultiSelect("students");
            $multiSelect->source()->fromArray($students);
            $multiSelect->addSortableAttribute("Form", $studentsForm);

    $row = $form->addRow();
        $row->addLabel("addByGroup", "Add by Group")->description("Add or remove students to trip by Class or Activity (scroll down for activities).");
        $column = $row->addColumn()->addClass("right");
            $column->addSelect("addStudentsByClass")->fromArray($groups)->placeholder("None");
            $column->addButton("Add", "addClass('Add')")->addClass("shortWidth")->setID("addButton");
            $column->addButton("Remove", "addClass('Remove')")->addClass("shortWidth")->setID("removeButton");
            

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    print $form->getOutput();

    if ($edit) {

        $daysList = array();
        foreach (explode(", ", $trip["multiDay"]) as $day) {
            $temp = explode(";", $day);
            if(count($temp) != 5) continue;
            $temp[0] = DateTime::createFromFormat("Y-m-d", $temp[0])->format("d/m/Y");
            $temp[1] = DateTime::createFromFormat("Y-m-d", $temp[1])->format("d/m/Y");
            $temp[2] = $temp[2] == 1;
            $temp[3] = DateTime::createFromFormat("H:i:s", $temp[3])->format("H:i");
            $temp[4] = DateTime::createFromFormat("H:i:s", $temp[4])->format("H:i");
            $daysList[] = $temp;
        }

        try {
            $dataCosts = array("tripPlannerRequestID" => $tripPlannerRequestID);
            $sqlCosts = 'SELECT title, description, cost FROM tripPlannerCostBreakdown WHERE tripPlannerRequestID=:tripPlannerRequestID ORDER BY tripPlannerCostBreakdownID';
            $resultCosts = $connection2->prepare($sqlCosts);
            $resultCosts->execute($dataCosts);
        } catch (PDOException $e) {
        }

        $costs = $resultCosts->fetchAll();

    ?>
    <script type="text/javascript">
        function addOption(name, people) {
            $('#' + name + "Source").find('option').each(function(){
                if (people.indexOf($(this).val()) >= 0) {
                    $('#' + name + "Destination").append($(this).clone());
                    $(this).detach().remove();
                }
            });
            sortSelects(name);
        }

        $(document).ready(function(){
            addOption('teachers', <?php print json_encode($tripTeachers)?>);
            addOption('students', <?php print json_encode($tripStudents)?>);

            //Add Cost
            var costs = <?php print json_encode($costs) ?>;
            for (var i = 0; i < costs.length; i++) {
                var cost = costs[i];

                addcostBlock();
                $("input[name='costName[" + (i+1) + "]']").val(cost["title"]);
                $("input[name='costValue[" + (i+1) + "]']").val(cost["cost"]);
                $("textarea[name='costDescription[" + (i+1) + "]']").text(cost["description"]);
            }

            //Days
            var multiday = <?php empty($trip["multiDay"]) ? print "false" : print "true" ?>;
            if (multiday) {
                $("#multipleDays").attr("checked", "checked").change();
                daysList = <?php print json_encode($daysList) ?>;
                dayID = daysList.length;
                for (var i = 0; i < daysList.length; i++) {
                    if (daysList[i] != null) {
                        $("#dayList").append($("<option>", {value: i, text: daysList[i][0] + (daysList[i][0] != daysList[i][1] ? " - " + daysList[i][1] : "")}));
                    }
                }
            } else {
                $("#startDate").val(<?php print $trip["date"]?>);
                $("#endDate").val(<?php print $trip["endDate"]?>);
                $("#allDay").attr("checked", <?php ($trip["startTime"] == null || $trip["endTime"] == null) ? print "'checked'" : print "''"?>);
                $("#startTime").val(<?php print $trip["startTime"]?>);
                $("#endTime").val(<?php print $trip["endTime"]?>);
            }
        });
    </script>
    <?php
    }
}   
?>
