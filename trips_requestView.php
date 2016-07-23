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

@session_start();

//Module includes
include "./modules/Trip Planner/moduleFunctions.php";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manage.php'>" . _("Manage Trip Requests") . "</a> > </div><div class='trailEnd'>" . _('View Request') . "</div>";
    print "</div>";
    if (isset($_GET["tripPlannerRequestID"])) {
        $tripPlannerRequestID = $_GET["tripPlannerRequestID"];

        $gibbonPersonID = $_SESSION[$guid]["gibbonPersonID"];
        $departments = getHOD($connection2, $gibbonPersonID);
        $departments2 = getDepartments($connection2, getOwner($connection2, $tripPlannerRequestID));
        $isHOD = false;

        foreach ($departments as $department) {
            if (in_array($department["gibbonDepartmentID"], $departments2)) {
                $isHOD = true;
                break;
            }
        }

        if (isApprover($connection2, $gibbonPersonID) || isOwner($connection2, $tripPlannerRequestID, $gibbonPersonID) || isInvolved($connection2, $tripPlannerRequestID, $gibbonPersonID) || $isHOD) {
            $databaseFail = false;

            try {
                $data = array("tripPlannerRequestID" => $tripPlannerRequestID);
                $sql = "SELECT creatorPersonID, timestampCreation, title, description, teacherPersonIDs, studentPersonIDs, location, date, startTime, endTime, riskAssessment, totalCost, status FROM tripPlannerRequests WHERE tripPlannerRequestID=:tripPlannerRequestID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $databaseFail = true;
            }

            if (!$databaseFail) {
                if($result->rowCount() == 1) {
                    $request = $result->fetch();

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }


                    $date = DateTime::createFromFormat("Y-m-d", $request["date"]);
                    $startTime = DateTime::createFromFormat("H:i:s", $request["startTime"]);
                    $endTime = DateTime::createFromFormat("H:i:s", $request["endTime"]);

                    $teachers = array();
                    foreach (explode(",", $request["teacherPersonIDs"]) as $teacher) {
                        $teachers[] = $teacher;
                    }

                    $students = array();
                    foreach (explode(",", $request["studentPersonIDs"]) as $student) {
                        $students[] = $student;
                    }
                    ?>
                    <form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Trip Planner/trips_requestViewProcess.php' ?>">
                        <table class="smallIntBorder fullWidth" cellspacing=0>
                            <tr class="break">
                                <td colspan=2>
                                    <h3>
                                        Basic Information
                                        <?php print "<div id='showBasic'  title='" . __($guid, 'Show/Hide') . "' style='margin-top: -5px; margin-left: 3px; padding-right: 1px; float: right; width: 24px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\")'></div>"; ?>
                                    </h3>
                                    <script type="text/javascript">
                                        $(document).ready(function(){
                                            $('#showBasic').unbind('click').click(function() {
                                                if ($("#basicInfo").is(":visible")) {
                                                    $("#basicInfo").css("display", "none");
                                                    $('#showBasic').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/plus.png' ?>')");
                                                } else {
                                                    $("#basicInfo").fadeIn("fast", $("#basicInfo").css("display","table-row-group"));
                                                    $('#showBasic').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/minus.png' ?>')");
                                                }
                                            });
                                        });
                                    </script>
                                </td>
                            </tr>
                            <tbody id='basicInfo'>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'Title') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="title" id="title" maxlength=60 value="<?php echo $request['title']; ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan=2> 
                                        <b><?php echo __($guid, 'Description') ?></b>
                                        <?php 
                                            echo '<p>';
                                            echo $request['description'];
                                            echo '</p>'
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'Status') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="status" id="status" maxlength=60 value="<?php echo $request['status']; ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'Date') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="date" id="date" maxlength=60 value="<?php echo $date->format('d/m/Y'); ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'Start Time') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="startTime" id="startTime" maxlength=60 value="<?php echo $startTime->format('H:i'); ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'End Time') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="endTime" id="endTime" maxlength=60 value="<?php echo $endTime->format('H:i'); ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'Location') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="location" id="location" maxlength=60 value="<?php echo $request['location']; ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                            </tbody>
                            <tr class="break">
                                <td colspan=2>
                                    <h3>
                                        <?php echo __($guid, 'Risk Assessment') ?>
                                        <?php print "<div id='showRisk'  title='" . __($guid, 'Show/Hide') . "' style='margin-top: -5px; margin-left: 3px; padding-right: 1px; float: right; width: 24px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\")'></div>"; ?>
                                    </h3>
                                    <script type="text/javascript">
                                        $(document).ready(function(){
                                            $('#showRisk').unbind('click').click(function() {
                                                if ($("#riskInfo").is(":visible")) {
                                                    $("#riskInfo").css("display","none");
                                                    $('#showRisk').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/plus.png' ?>')");
                                                } else {
                                                    $("#riskInfo").slideDown("fast", $("#riskInfo").css("display","table-row-group"));
                                                    $('#showRisk').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/minus.png' ?>')");
                                                }
                                            });
                                        });
                                    </script>
                                </td>
                            </tr>
                            <tbody id='riskInfo'>
                                <tr>
                                    <td colspan=2> 
                                        <?php 
                                            echo '<p>';
                                            echo $request['riskAssessment'];
                                            echo '</p>'
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                            <tr class="break">
                                <td colspan=2>
                                    <h3>
                                        People Involved
                                        <?php print "<div id='showPeople'  title='" . __($guid, 'Show/Hide') . "' style='margin-top: -5px; margin-left: 3px; padding-right: 1px; float: right; width: 24px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\")'></div>"; ?>
                                    </h3>
                                    <script type="text/javascript">
                                        $(document).ready(function(){
                                            $('#showPeople').unbind('click').click(function() {
                                                if ($("#peopleInfo").is(":visible")) {
                                                    $("#peopleInfo").css("display","none");
                                                    $('#showPeople').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/plus.png' ?>')");
                                                } else {
                                                    $("#peopleInfo").slideDown("fast", $("#peopleInfo").css("display","table-row-group"));
                                                    $('#showPeople').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/minus.png' ?>')");
                                                }
                                            });
                                        });
                                    </script>
                                </td>
                            </tr>
                            <tbody id="peopleInfo">
                                <tr>
                                    <td colspan=2>
                                        <b><?php echo __($guid, 'Teachers') ?></b>
                                        <table class='noIntBorder' cellspacing='0' style='width:100%;'>
                                            <tr>
                                                <?php
                                                    for ($i = 0; $i < count($teachers); $i++) {
                                                        $teacher = $teachers[$i];
                                                        if ($i % 5 == 0) {
                                                            print "</tr>";
                                                            print "<tr>";
                                                        } 
                                                        getPersonBlock($guid, $connection2, $teacher, "Staff");
                                                    } 
                                                ?>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan=2>
                                        <b><?php echo __($guid, 'Students') ?></b>
                                        <table class='noIntBorder' cellspacing='0' style='width:100%;'>
                                            <tr>
                                                <?php
                                                    for ($i = 0; $i < count($students); $i++) {
                                                        $student = $students[$i];
                                                        if ($i % 5 == 0) {
                                                            print "</tr>";
                                                            print "<tr>";
                                                        } 
                                                        getPersonBlock($guid, $connection2, $student, "Student");
                                                    } 
                                                ?>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                            <tr class="break">
                                <td colspan=2>
                                    <h3>
                                        Cost Breakdown
                                        <?php print "<div id='showCost'  title='" . __($guid, 'Show/Hide') . "' style='margin-top: -5px; margin-left: 3px; padding-right: 1px; float: right; width: 24px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\")'></div>"; ?>
                                    </h3>
                                    <script type="text/javascript">
                                        $(document).ready(function(){
                                            $('#showCost').unbind('click').click(function() {
                                                if ($("#costInfo").is(":visible")) {
                                                    $("#costInfo").css("display","none");
                                                    $('#showCost').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/plus.png' ?>')");
                                                } else {
                                                    $("#costInfo").slideDown("fast", $("#costInfo").css("display","table-row-group"));
                                                    $('#showCost').css("background-image", "url('<?php print $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/minus.png' ?>')");
                                                }
                                            });
                                        });
                                    </script>
                                </td>
                            </tr>
                            <tbody id="costInfo">
                                <tr>
                                    <td colspan=2>
                                        <table cellspacing='0' style='width: 100%; $style4'>
                                            <tr class='head'>
                                                <th style='text-align: left; padding-left: 10px'>
                                                    <?php print __($guid, 'Name'); ?>
                                                </th>
                                                <th style='text-align: left'>
                                                    <?php print __($guid, 'Description'); ?>
                                                </th>
                                                <th style='text-align: left'>
                                                    <?php 
                                                        print __($guid, 'Cost') . "<br/>"; 
                                                        if ($_SESSION[$guid]['currency'] != '') {
                                                            print "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                                                        }
                                                    ?>
                                                </th>
                                            </tr>
                                            <?php
                                                try {
                                                    $dataCosts = array("tripPlannerRequestID" => $tripPlannerRequestID);
                                                    $sqlCosts = 'SELECT tripPlannerCostBreakdownID, title, description, cost FROM tripPlannerCostBreakdown WHERE tripPlannerRequestID=:tripPlannerRequestID ORDER BY tripPlannerCostBreakdownID';
                                                    $resultCosts = $connection2->prepare($sqlCosts);
                                                    $resultCosts->execute($dataCosts);
                                                } catch (PDOException $e) {
                                                    print "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                $count = 0;
                                                while ($rowCosts = $resultCosts->fetch()) {
                                                    $rowNum = 'odd';
                                                    if ($count % 2 == 0) {
                                                        $rowNum = 'even';
                                                    }

                                                    print "<tr style='height: 25px' class=$rowNum>";
                                                        print "<td style='padding-left: 10px'>";
                                                            print $rowCosts['title'];
                                                        print "</td>";
                                                        print "<td>";
                                                            print $rowCosts['description'];
                                                        print "</td>";
                                                        print "<td>";
                                                            $cost = "";
                                                            if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                                                $cost = substr($_SESSION[$guid]['currency'], 4).' ';
                                                            }
                                                            $cost .= number_format($rowCosts['cost'], 2, '.', ',');
                                                            print $cost;
                                                        print "</td>";
                                                    print "</tr>";
                                                    $count++;
                                                }
                                            ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td> 
                                        <b><?php echo __($guid, 'Total Cost') ?> *</b><br/>
                                    </td>
                                    <td class="right">
                                        <input readonly name="totalCost" id="totalCost" maxlength=60 value="<?php echo $_SESSION[$guid]['currency'] . ' ' . $request['totalCost']; ?>" type="text" class="standardWidth">
                                    </td>
                                </tr>
                            </tbody>
                            <tr class="break">
                                <td colspan=2>
                                    <h3>
                                        Log
                                    </h3>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2>
                                    <?php
                                        print getRequestLog($guid, $connection2, $tripPlannerRequestID);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=2> 
                                    <b><?php echo __($guid, 'Comment') ?></b><br/>
                                    <textarea name="comment" id="comment" rows=8 style="resize:vertical; width: 100%"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
                                </td>
                                <td class="right">
                                    <input name="tripPlannerRequestID" id="tripPlannerRequestID" value="<?php echo $tripPlannerRequestID ?>" type="hidden">
                                    <input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
                                </td>
                            </tr>
                        </table>
                    </form>
                    <?php
                } else {
                    print "<div class='error'>";
                        print "No request selected.";
                    print "</div>";
                }
            } else {
                print "<div class='error'>";
                    print "Database error.";
                print "</div>";
            }
        } else {
            print "<div class='error'>";
                print "You do not have access to this action.";
            print "</div>";
        }
    } else {    
        print "<div class='error'>";
            print "No request selected.";
        print "</div>";
    }
}   
?>
