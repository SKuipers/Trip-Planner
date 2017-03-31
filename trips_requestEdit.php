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
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manage.php'>" . _("Manage Trip Request") . "</a> > </div><div class='trailEnd'>" . _('Edit Request') . "</div>";
    print "</div>";

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (isset($_GET["tripPlannerRequestID"])) {
        $tripPlannerRequestID = $_GET["tripPlannerRequestID"];
        if (isOwner($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"])) {
            renderTrip($guid, $connection2, $tripPlannerRequestID, "Edit");
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