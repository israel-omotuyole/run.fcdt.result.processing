<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->database();
    }

    public function index() {
        $data['user_role']         = 'Exam Officer';
        $data['session_name']      = '2025/2026 — First Semester';
        $data['department_name']   = 'Computer Science';
        $data['total_audited']     = 0;
        $data['score_anomalies']   = 0;
        $data['borderline_scores'] = 0;
        $data['cleared_for_grad']  = 0;
        $data['candidates']        = array();

        $this->load->view('dashboard_view', $data);
    }

    public function process_upload() {
        $config['upload_path']   = './uploads/';
        $config['allowed_types'] = 'pdf';
        $config['max_size']      = 10240;
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('result_file')) {
            $data['error']             = $this->upload->display_errors();
            $data['user_role']         = 'Exam Officer';
            $data['session_name']      = '2025/2026 — First Semester';
            $data['department_name']   = 'Computer Science';
            $data['total_audited']     = 0;
            $data['score_anomalies']   = 0;
            $data['borderline_scores'] = 0;
            $data['cleared_for_grad']  = 0;
            $data['candidates']        = array();
            $this->load->view('dashboard_view', $data);
        } else {
            $upload_data = $this->upload->data();
            $file_path   = $upload_data['full_path'];
            $this->parse_and_render_dashboard($file_path);
        }
    }

    private function parse_and_render_dashboard($file_path) {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($file_path);
        $text   = $pdf->getText();

        $lines = explode("\n", $text);
        $candidates = array();

        $borderlines_rule = array(39, 49, 59, 69);
        $score_anomalies_count = 0;
        $borderline_count = 0;
        $cleared_count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $line = preg_replace('/(CSC\/\d{4,8})\//i', '$1', $line);

            $matched = false;
            // Complete line match
            if (preg_match('/([A-Z]{3}\/\d{4}\/?\d{0,4})\s+([A-Za-z\s]+?)\s+(\d{1,2})\s+(\d{1,2})\s+(\d{1,3})\s+([A-F])/i', $line, $m)) {
                $matric = trim($m[1]);
                $name   = trim($m[2]);
                $ca     = (int)$m[3];
                $exam   = (int)$m[4];
                $total  = (int)$m[5];
                $grade  = trim($m[6]);
                $matched = true;
            } 
            // Raw CA + Exam line match
            elseif (preg_match('/(\d+)?\s*([A-Z]{3}\/\d{4}\/?\d{0,4})\s+([A-Za-z\s]+?)\s+(\d{1,2})\s+(\d{1,2})$/i', $line, $m)) {
                $matric = trim($m[2]);
                $name   = trim($m[3]);
                $ca     = (int)$m[4];
                $exam   = (int)$m[5];
                $total  = $ca + $exam;

                if ($total >= 70)      $grade = 'A';
                elseif ($total >= 60) $grade = 'B';
                elseif ($total >= 50) $grade = 'C';
                elseif ($total >= 45) $grade = 'D';
                elseif ($total >= 40) $grade = 'E';
                else                  $grade = 'F';

                $matched = true;
            }

            if ($matched) {
                $flags = array();
                $has_anomaly = false;

                if ($total == 0) {
                    $flags[] = array('type' => 'error', 'label' => 'Zero Score (CSC401)');
                    $score_anomalies_count++;
                    $has_anomaly = true;
                }
                if (in_array($total, $borderlines_rule)) {
                    $flags[] = array('type' => 'borderline', 'label' => 'Borderline ' . $total . ' (CSC401)');
                    $borderline_count++;
                    $has_anomaly = true;
                }
                if ($total < 40 && $total > 0) {
                    $flags[] = array('type' => 'error', 'label' => 'Failed Compulsory');
                    $has_anomaly = true;
                }

                if (!$has_anomaly) {
                    $flags[] = array('type' => 'clean', 'label' => 'Clean');
                    $grad_status_raw   = 'cleared';
                    $grad_status_label = 'Cleared (Pass)';
                    $cleared_count++;
                } elseif (!empty($flags) && $flags[0]['type'] === 'borderline') {
                    $grad_status_raw   = 'pending';
                    $grad_status_label = 'Pending Review';
                } else {
                    $grad_status_raw   = 'uncleared';
                    $grad_status_label = 'Uncleared';
                }

                // Construct dashboard record
                $candidates[] = array(
                    'id'                => count($candidates) + 1,
                    'matric'            => $matric,
                    'name'              => $name,
                    'tur'               => 21,  // Standard semester credit load
                    'tup'               => ($total >= 40) ? 140 : 137,
                    'cgpa'              => ($total >= 70) ? 4.10 : (($total >= 50) ? 3.45 : 1.95),
                    'flags'             => $flags,
                    'grad_status_raw'   => $grad_status_raw,
                    'grad_status_label' => $grad_status_label,
                    'score'             => $total,
                    'grade'             => $grade
                );
            }
        }

        // Return everything directly into dashboard_view
        $data['user_role']         = 'Exam Officer';
        $data['session_name']      = '2025/2026 — First Semester';
        $data['department_name']   = 'Computer Science';
        $data['total_audited']     = count($candidates);
        $data['score_anomalies']   = $score_anomalies_count;
        $data['borderline_scores'] = $borderline_count;
        $data['cleared_for_grad']  = $cleared_count;
        $data['candidates']        = $candidates;

        $this->load->view('dashboard_view', $data);
    }
}