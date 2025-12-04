<?php
class LaporanQuery {
	private $bisnisId;
	public function __construct($bisnisId) {
		$this->bisnisId = $bisnisId;
	}

	// Dummy: Replace with real query logic
	public function getKinerjaKaryawan($karyawanId, $filterType, $tanggalMulai = null, $tanggalSelesai = null) {
		return [
			'estimasi_gaji' => 1450000,
			'total_kiloan' => 725,
			'total_kehadiran' => 22,
			'rincian' => [
				[
					'nota' => '0913123',
					'nama_customer' => 'Kosimudin',
					'tanggal_selesai' => '2025-11-21 16:00:00',
					'berat_selesai' => 5
				],
				[
					'nota' => '0913120',
					'nama_customer' => 'Jajang',
					'tanggal_selesai' => '2025-11-20 14:30:00',
					'berat_selesai' => 2
				],
				[
					'nota' => '0913119',
					'nama_customer' => 'Siti',
					'tanggal_selesai' => '2025-11-20 10:15:00',
					'berat_selesai' => 3
				]
			]
		];
	}

	public function getAbsensiKaryawan($karyawanId, $filterType, $tanggalMulai = null, $tanggalSelesai = null) {
		return [
			[
				'jenis_absen' => 'Absen Masuk',
				'tanggal' => '2025-11-21',
				'jam_masuk' => '08:01',
				'jam_pulang' => null
			],
			[
				'jenis_absen' => 'Absen Pulang',
				'tanggal' => '2025-11-20',
				'jam_masuk' => null,
				'jam_pulang' => '17:05'
			],
			[
				'jenis_absen' => 'Absen Masuk',
				'tanggal' => '2025-11-20',
				'jam_masuk' => '07:58',
				'jam_pulang' => null
			]
		];
	}
}

function formatRupiah($angka) {
	return 'Rp ' . number_format($angka, 0, ',', '.');
}
function formatTanggal($tanggal) {
	return date('d M Y', strtotime($tanggal));
}
function formatTanggalWaktu($tanggal) {
	return date('d M Y, H:i', strtotime($tanggal));
}
function formatJam($jam) {
	return $jam ? date('H:i', strtotime($jam)) : '-';
}
