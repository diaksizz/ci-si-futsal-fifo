<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include('Super.php');

class Antrian_fcfs extends Super {
    public function __construct() {
        parent::__construct();
        $this->tema           = "flexigrid"; /** datatables / flexigrid **/
        $this->nama_view      = "Statistik Antrian FCFS";

        $this->load->database();
        $this->load->model('Antrian_model');
    }

    public function index() {
        $data = [];

        // Ambil tanggal dari input pengguna
        $selected_date = $this->input->get('tanggal');
        
        // Ambil data antrian berdasarkan tanggal yang dipilih
        if ($selected_date) {
            $antrian = $this->Antrian_model->get_antrian_by_date($selected_date);
        } else {
            $antrian = $this->Antrian_model->get_antrian();
        }

        // Menghitung statistik FCFS
        $antrian_statistik = $this->calculate_fcfs($antrian);
        
        // Menghitung rata-rata waktu tunggu dan waktu selesai
        $avg_times = $this->calculate_average_times($antrian_statistik);
        
        $data = array_merge($data, $this->generateBreadcumbs());
        $data = array_merge($data, $this->generateData());
        $data['antrian_statistik'] = $antrian_statistik;
        $data['avg_waiting_time'] = $avg_times['avg_waiting_time'];
        $data['avg_turnaround_time'] = $avg_times['avg_turnaround_time'];
        $data['page'] = 'v_antrian_statistik_view';

        // Memuat view dan mengirim data
        $this->load->view('admin/'.$this->session->userdata('theme').'/v_index', $data);
    }

    private function generateBreadcumbs(){
        $data['breadcumbs'] = array(
                array(
                    'nama'=>'Dashboard',
                    'icon'=>'fa fa-dashboard',
                    'url'=>'admin/dashboard'
                ),
                array(
                    'nama'=>'Admin',
                    'icon'=>'fa fa-users',
                    'url'=>'admin/useradmin'
                ),
            );
        return $data;
    }

    private function calculate_fcfs($antrian) {
        $waiting_time = 0;
        $service_time = 5; // Asumsi waktu pelayanan 5 menit
        $current_time = 0;

        $statistik = [];

        foreach ($antrian as $row) {
            $arrival_time = strtotime($row['tgl_antrian']); // waktu tiba dalam detik
            $start_time = max($current_time, $arrival_time); // waktu mulai adalah waktu paling lambat antara waktu tiba dan waktu saat ini
            $finish_time = $start_time + $service_time * 60; // waktu selesai dalam detik
            $waiting_time = $start_time - $arrival_time; // waktu tunggu dalam detik
            $turnaround_time = $finish_time - $arrival_time; // turn around time dalam detik

            $statistik[] = [
                'id' => $row['id_antrian'],
                'no_antrian' => $row['no_antrian'],
                'tgl_antrian' => $row['tgl_antrian'],
                'arrival_time' => date('H:i:s', $arrival_time),
                'start_time' => date('H:i:s', $start_time),
                'service_time' => $service_time,
                'finish_time' => date('H:i:s', $finish_time),
                'waiting_time' => $waiting_time / 60, // dalam menit
                'turnaround_time' => $turnaround_time / 60 // dalam menit
            ];

            $current_time = $finish_time; // perbarui waktu saat ini untuk antrian berikutnya
        }

        return $statistik;
    }

    private function calculate_average_times($statistik) {
        $total_waiting_time = 0;
        $total_turnaround_time = 0;
        $count = count($statistik);

        foreach ($statistik as $item) {
            $total_waiting_time += $item['waiting_time'];
            $total_turnaround_time += $item['turnaround_time'];
        }

        $avg_waiting_time = $count > 0 ? $total_waiting_time / $count : 0;
        $avg_turnaround_time = $count > 0 ? $total_turnaround_time / $count : 0;

        return [
            'avg_waiting_time' => $this->format_time($avg_waiting_time),
            'avg_turnaround_time' => $this->format_time($avg_turnaround_time)
        ];
    }

    private function format_time($minutes) {
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        $seconds = ($minutes - floor($minutes)) * 60;
        return $hours . ' jam ' . floor($minutes) . ' menit ';
    }    
}
?>
