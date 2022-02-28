<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Move questions page.
 *
 * @package    qbank_bulkedit
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../editlib.php');
require_once(__DIR__ . '/../../../lib/formslib.php');

global $DB, $OUTPUT, $PAGE, $COURSE;

$editselected = optional_param('edit', false, PARAM_BOOL);
$findtext = optional_param('findtext','',PARAM_TEXT);
$replacement = optional_param('replacement','',PARAM_TEXT);

$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$category = optional_param('category', null, PARAM_SEQUENCE);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);
$editquestionselected = optional_param('editquestionsselected', null, PARAM_RAW);

class edit_form extends moodleform {

    /**
     * Build the form definition.
     *
     * This adds all the form fields that the export questions feature needs.
     */
    protected function definition() {
        $mform = $this->_form;
        $placeholderfind = "Text in question body to be replaced";
        $mform->addElement('textarea', 'findtext', 'Text to find', ['width' => '200', 'rows' => 5, 'placeholder' => $placeholderfind]);
        $mform->setType('findtext', PARAM_TEXT);
        $mform->addHelpButton('findtext', 'questiontext', 'qtype_gapfill');

        $placeholderreplace = "Replacement text";
        $mform->addElement('textarea', 'replacement', 'Replacement text', ['rows' => 5, 'placeholder' => $placeholderreplace]);
        $mform->setType('replacement', PARAM_TEXT);
        $mform->addHelpButton('replacement', 'wronganswers', 'qtype_gapfill');
    }
}

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
}

\core_question\local\bank\helper::require_plugin_enabled('qbank_bulkedit');

if ($cmid) {
    list($module, $cm) = get_module_from_cmid($cmid);
    require_login($cm->course, false, $cm);
    $thiscontext = context_module::instance($cmid);
} else if ($courseid) {
    require_login($courseid, false);
    $thiscontext = context_course::instance($courseid);
} else {
    throw new moodle_exception('missingcourseorcmid', 'question');
}

$contexts = new core_question\local\bank\question_edit_contexts($thiscontext);
$url = new moodle_url('/question/bank/bulkedit/edit.php');

$PAGE->set_url($url);
$streditingquestions = get_string('editquestions', 'qbank_bulkedit');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($COURSE->fullname);

if ($category) {
    list($tocategoryid, $contextid) = explode(',', $category);
    if (! $tocategory = $DB->get_record('question_categories',
        ['id' => $tocategoryid, 'contextid' => $contextid])) {
        throw new \moodle_exception('cannotfindcate', 'question');
    }
}

if ($editquestionselected && $confirm && confirm_sesskey()) {
    if ($confirm == md5($editquestionselected)) {
       \qbank_bulkedit\helper::bulk_edit_questions($editquestionselected, $findtext, $replacement);
    }
   // redirect(new moodle_url($returnurl, ['category' => "{$tocategoryid},{$contextid}"]));
}

echo $OUTPUT->header();

if ($editselected) {
    $rawquestions = $_REQUEST;
    list($questionids, $questionlist) = \qbank_bulkedit\helper::process_question_ids($rawquestions);
    // No questions were selected.
    if (!$questionids) {
        redirect($returnurl);
    }
    // Create the urls.
    $editparam = [
        'editquestionsselected' => $questionlist,
        'confirm' => md5($questionlist),
        'sesskey' => sesskey(),
        'returnurl' => $returnurl,
        'cmid' => $cmid,
        'courseid' => $courseid,
    ];
    $editurl = new \moodle_url($url, $editparam);

    $addcontexts = $contexts->having_cap('moodle/question:add');
    $displaydata = \qbank_bulkedit\helper::get_displaydata($editurl, $returnurl);
    // $editform = new edit_form();
    //$displaydata['editor'] = $editform->render();

    echo $PAGE->get_renderer('qbank_bulkedit')->render_bulk_edit_form($displaydata);
}

echo $OUTPUT->footer();
