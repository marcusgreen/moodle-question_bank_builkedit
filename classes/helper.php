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

namespace qbank_bulkedit;

/**
 * Bulk move helper.
 *
 * @package    qbank_bulkedit
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Bulk move questions to a category.
     *
     * @param string $editquestionselected comma separated string of questions to be moved.
     */
    public static function bulk_edit_questions(string $editquestionselected, string $find, string $replace): void {
        global $DB;
        if ($questionids = explode(',', $editquestionselected)) {
            list($usql, $params) = $DB->get_in_or_equal($questionids);
            $sql = "SELECT q.*, c.contextid
                      FROM {question} q
                      JOIN {question_versions} qv ON qv.questionid = q.id
                      JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                      JOIN {question_categories} c ON c.id = qbe.questioncategoryid
                     WHERE q.id
                     {$usql}";
            $questions = $DB->get_records_sql($sql, $params);
            foreach ($questions as $question) {
                $updated = str_replace($find, $replace, $question->questiontext );
                $DB->update_record('question', ['id' => $question->id, 'questiontext' => $updated], false);
            }
        }
    }

    /**
     * Get the display data for the move form.
     *
     * @param array $addcontexts the array of contexts to be considered in order to render the category select menu.
     * @param \moodle_url $moveurl the url where the move script will point to.
     * @param \moodle_url $returnurl return url in case the form is cancelled.
     * @return array the data to be rendered in the mustache where it contains the dropdown, move url and return url.
     */
    public static function get_displaydata(\moodle_url $editurl, \moodle_url $returnurl): array {
        $displaydata = [];
        $displaydata ['editurl'] = $editurl;
        $displaydata['returnurl'] = $returnurl;
        return $displaydata;
    }

    /**
     * Process the question came from the form post.
     *
     * @param array $rawquestions raw questions came as a part of post.
     * @return array question ids got from the post are processed and structured in an array.
     */
    public static function process_question_ids(array $rawquestions): array {
        $questionids = [];
        $questionlist = '';
        foreach (array_keys($rawquestions) as $key) {
            // Parse input for question ids.
            if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                $key = $matches[1];
                $questionids[] = $key;
            }
        }
        if (!empty($questionids)) {
            $questionlist = implode(',', $questionids);
        }
        return [$questionids, $questionlist];
    }
}
