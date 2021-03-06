<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: Queen's University
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_CourseContact extends Models_Base {
    protected $id, $course_id, $assessor_value, $assessor_type, $visible, $created_by, $created_date, $updated_by, $updated_date, $deleted_date;
    
    protected static $table_name = "cbl_course_contacts";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";

    public function getId() {
        return $this->id;
    }

    public function getCourseId() {
        return $this->course_id;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getVisible() {
        return $this->visible;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setVisible($visible) {
        $this->visible = $visible;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function fetchRowByID($id) {
        $self = new self();
        $constraints = array(
            array("key" => "id", "value" => $id, "method" => "=")
        );
        return $self->fetchRow($constraints);
    }

    public function fetchRowByAssessorValueAssessorTypeCourseID($assessor_value, $assessor_type, $course_id) {
        $self = new self();
        $constraints = array(
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        );
        return $self->fetchRow($constraints);
    }

    public function fetchAllByCourseID($course_id) {
        $self = new self();
        $constraints = array(
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        );
        return $self->fetchAll($constraints);
    }

    public function insertCourseContactRecord($course_id, $assessor_value, $assessor_type) {
        global $ENTRADA_USER, $translate;
        $insert_record = true;

        if ($assessor_type == "external_hash") {
            $assessor_type = "external";
        } else if ($assessor_type == "proxy_id") {
            $assessor_type = "internal";
        }

        if (!$assessor_value || ($assessor_type != "internal" && $assessor_type != "external")) {
            $insert_record = false;
        }

        if ($insert_record && $assessor_type == "internal") {
            $roles = Models_User_Access::fetchAllByUserIDOrganisationID($assessor_value, $ENTRADA_USER->getActiveOrganisation());

            if (!empty($roles)) {
                $is_faculty = false;

                foreach ($roles as $role) {
                    if ($role->getGroup() == "faculty") {
                        $is_faculty = true;
                    }
                }

                if (!$is_faculty) {
                    $insert_record = false;
                }
            }
        }

        if ($insert_record && $this->fetchRowByAssessorValueAssessorTypeCourseID($assessor_value, $assessor_type, $course_id)) {
            $insert_record = false;
        }

        if ($insert_record) {
            $course_contact = new Models_Assessments_Distribution_CourseContact(array(
                    "course_id" => $course_id,
                    "assessor_value" => $assessor_value,
                    "assessor_type" => $assessor_type,
                    "visible" => 1,
                    "created_by" => $ENTRADA_USER->getActiveID(),
                    "created_date" => time(),
                    "updated_by" => $ENTRADA_USER->getActiveID(),
                    "updated_date" => time(),
                    "deleted_date" => NULL
                )
            );

            if (!$course_contact->insert()) {
                add_error($translate->_("An error occurred while attempting to insert a course contact"));
            }
        }
    }

    public function getFaculty($proxy_id, $organisation_id, $url = null, $search_term = null, $add_externals = true) {
        $users = array();
        $courses = Models_Course::getUserCourses($proxy_id, $organisation_id);

        if ($courses) {
            $faculty = array();
            $assessment_user = new Entrada_Utilities_AssessmentUser();

            foreach ($courses as $course) {
                $course_contact_list = $this->fetchAllByCourseID($course->getID());
                foreach ($course_contact_list as $course_contact) {
                    $faculty[] = array($course_contact->getAssessorValue() => $course_contact->getAssessorType());
                }
            }

            foreach ($faculty as $faculty_member) {
                foreach ($faculty_member as $proxy_id => $course_contact_type) {
                    if ($course_contact_type == "internal") {
                        $user = Models_User::fetchRowByID($proxy_id);
                        if ($user) {
                            $add_record = false;
                            if (is_null($search_term)) {
                                $add_record = true;
                            } else if (strpos(strtolower($user->getFullname(false)), strtolower($search_term)) !== false) {
                                $add_record = true;
                            }

                            if ($add_record) {
                                $users[$user->getID()] = new Entrada_Utilities_AssessmentUser(array(
                                    "proxy_id" => $user->getID(),
                                    "firstname" => $user->getFirstname(),
                                    "lastname" => $user->getLastname(),
                                    "email" => $user->getEmail(),
                                    "number" => $user->getNumber(),
                                    "assessment_count" => null,//$assessment_user->getUserAssessmentCount($user->getID(), $organisation_id, "faculty"),
                                    "assessment_url" => is_null($url) ? "" : $url . "/assessments/faculty?proxy_id=" . $user->getID(),
                                    "photo_url" => is_null($url) ? "" : $url . "/api/photo.api.php/" . $user->getID() . "/official"
                                ));
                            }
                        }
                    } else {
                        $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($proxy_id);
                        if ($external_assessor) {
                            $add_record = false;
                            if (is_null($search_term)) {
                                $add_record = true;
                            } else if (strpos(strtolower($external_assessor->getFirstname() . " " . $external_assessor->getLastname()), strtolower($search_term)) !== false) {
                                $add_record = true;
                            }

                            if ($add_record && $add_externals) {
                                $users[$external_assessor->getID()] = new Models_Assessments_Distribution_ExternalAssessor(array(
                                    "eassessor_id" => $external_assessor->getID(),
                                    "firstname" => $external_assessor->getFirstname(),
                                    "lastname" => $external_assessor->getLastname(),
                                    "email" => $external_assessor->getEmail()
                                ));
                            }
                        }
                    }
                }
            }
        }
        uasort($users, array("Models_Assessments_Distribution_CourseContact", "compareUser"));

        return $users;
    }

    private function compareUser($user1, $user2) {
        if (strtolower($user1->getLastname()) == strtolower($user2->getLastname())) {
            return strcmp(strtolower($user1->getFirstname()), strtolower($user2->getLastname()));
        }
        return strcmp(strtolower($user1->getLastname()), strtolower($user2->getLastname()));
    }
}