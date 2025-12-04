<?php
// File untuk menangani semua query database laporan
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

class LaporanQuery {
    private $conn;
    private $bisnisId;
    
    public function __construct($bisnisId) {
        global $conn;
        $this->conn = $conn;
        $this->bisnisId = $bisnisId;
    }
    
    /**
     * Get bisnis ID
     */
    public function getBisnisId() {
        return $this->bisnisId;
    }
    
    /**
     * Generate WHERE clause berdasarkan filter type
     */
    private function getWhereClause($filterType, $tanggalMulai = null, $tanggalSelesai = null, $tableAlias = '') {
        $column = $tableAlias ? $tableAlias . '.created_at' : 'created_at';
        
        switch ($filterType) {
            case 'hari_ini':
                return ' AND DATE(' . $column . ') = CURDATE()';
            case '7_hari':
                return ' AND ' . $column . ' >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
            case 'bulan_ini':
                return ' AND YEAR(' . $column . ') = YEAR(CURDATE()) AND MONTH(' . $column . ') = MONTH(CURDATE())';
            case 'tahun_ini':
                return ' AND YEAR(' . $column . ') = YEAR(CURDATE())';
            case 'kustom':
                if ($tanggalMulai && $tanggalSelesai) {
                    return ' AND DATE(' . $column . ') BETWEEN ? AND ?';
                }
                return '';
            default:
                return ' AND YEAR(' . $column . ') = YEAR(CURDATE()) AND MONTH(' . $column . ') = MONTH(CURDATE())';
        }
    }
    
    /**
     * Generate parameter array berdasarkan filter
     */
    private function getParams($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        $params = [$this->bisnisId];
        
        if ($filterType === 'kustom' && $tanggalMulai && $tanggalSelesai) {
            $params[] = $tanggalMulai;
            $params[] = $tanggalSelesai;
        }
        
        return $params;
    }
    
    /**
     * Query data pendapatan
     */
    public function getPendapatanData($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            // Query total pendapatan dan transaksi
            $sql = "SELECT 
                COUNT(*) as total_transaksi,
                COALESCE(SUM(total_harga), 0) as total_pendapatan,
                COALESCE(AVG(total_harga), 0) as rata_rata_transaksi
            FROM transaksi 
            WHERE bisnis_id = ? AND status != 'batal'" . $whereClause;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: ['total_transaksi' => 0, 'total_pendapatan' => 0, 'rata_rata_transaksi' => 0];
            
        } catch (PDOException $e) {
            error_log('Error pendapatan: ' . $e->getMessage());
            return ['total_transaksi' => 0, 'total_pendapatan' => 0, 'rata_rata_transaksi' => 0];
        }
    }
    
    /**
     * Query layanan terlaris
     */
    public function getLayananTerlaris($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $sql = "SELECT l.nama_layanan, COUNT(dt.detail_id) as jumlah_transaksi
            FROM detail_transaksi dt
            JOIN transaksi t ON dt.transaksi_id = t.transaksi_id
            JOIN layanan l ON dt.layanan_id = l.layanan_id
            WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause . "
            GROUP BY l.layanan_id, l.nama_layanan
            ORDER BY jumlah_transaksi DESC
            LIMIT 5";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error layanan terlaris: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query rincian transaksi terbaru
     */
    public function getRincianTransaksi($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai, 't');
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $sql = "SELECT t.no_nota, t.total_harga, t.status, t.created_at,
                   COALESCE(p.nama, 'Tanpa Nama') as nama_pelanggan
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause . "
            ORDER BY t.created_at DESC
            LIMIT 10";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error rincian transaksi: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query data pengeluaran
     */
    public function getPengeluaranData($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            // Query total pengeluaran
            $sql = "SELECT 
                COUNT(*) as total_item,
                COALESCE(SUM(jumlah), 0) as total_pengeluaran
            FROM pengeluaran 
            WHERE bisnis_id = ?" . $whereClause;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $data = $result ?: ['total_item' => 0, 'total_pengeluaran' => 0];
            
            // Query kategori pengeluaran terbesar
            $sql = "SELECT kategori 
            FROM pengeluaran 
            WHERE bisnis_id = ?" . $whereClause . "
            GROUP BY kategori 
            ORDER BY SUM(jumlah) DESC 
            LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $data['pengeluaran_terbesar'] = $result ? ucfirst($result['kategori']) : '-';
            
            return $data;
            
        } catch (PDOException $e) {
            error_log('Error pengeluaran: ' . $e->getMessage());
            return ['total_item' => 0, 'total_pengeluaran' => 0, 'pengeluaran_terbesar' => '-'];
        }
    }
    
    /**
     * Query kategori pengeluaran untuk chart
     */
    public function getKategoriPengeluaran($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $sql = "SELECT kategori, SUM(jumlah) as total
            FROM pengeluaran 
            WHERE bisnis_id = ?" . $whereClause . "
            GROUP BY kategori
            ORDER BY total DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error kategori pengeluaran: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query pengeluaran terbaru
     */
    public function getPengeluaranTerbaru($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $sql = "SELECT keterangan, jumlah, tanggal, kategori
            FROM pengeluaran 
            WHERE bisnis_id = ?" . $whereClause . "
            ORDER BY tanggal DESC, created_at DESC
            LIMIT 5";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error pengeluaran terbaru: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query data pelanggan
     */
    public function getPelangganData($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $data = ['total_pelanggan' => 0, 'pelanggan_baru' => 0, 'rata_rata_belanja' => 0];
            
            // Query total pelanggan
            $sql = "SELECT COUNT(*) as total_pelanggan FROM pelanggan WHERE bisnis_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->bisnisId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $data['total_pelanggan'] = (int)$result['total_pelanggan'];
            }
            
            // Query pelanggan baru (30 hari terakhir)
            $sql = "SELECT COUNT(*) as pelanggan_baru 
            FROM pelanggan 
            WHERE bisnis_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->bisnisId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $data['pelanggan_baru'] = (int)$result['pelanggan_baru'];
            }
            
            // Query rata-rata belanja
            $sql = "SELECT COALESCE(AVG(t.total_harga), 0) as rata_rata_belanja
            FROM transaksi t
            WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $data['rata_rata_belanja'] = (float)$result['rata_rata_belanja'];
            }
            
            return $data;
            
        } catch (PDOException $e) {
            error_log('Error pelanggan: ' . $e->getMessage());
            return ['total_pelanggan' => 0, 'pelanggan_baru' => 0, 'rata_rata_belanja' => 0];
        }
    }
    
    /**
     * Query pelanggan teratas
     */
    public function getPelangganTeratas($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai, 't');
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $sql = "SELECT p.nama, SUM(t.total_harga) as total_belanja
            FROM pelanggan p
            JOIN transaksi t ON p.pelanggan_id = t.pelanggan_id
            WHERE p.bisnis_id = ? AND t.status != 'batal'" . $whereClause . "
            GROUP BY p.pelanggan_id, p.nama
            ORDER BY total_belanja DESC
            LIMIT 5";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error pelanggan teratas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query data karyawan
     */
    public function getKaryawanData($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $data = ['total_karyawan' => 0, 'total_selesai' => 0, 'rata_rata_harian' => 0];
            
            // Query total karyawan aktif
            $sql = "SELECT COUNT(*) as total_karyawan 
            FROM karyawan 
            WHERE bisnis_id = ? AND status = 'aktif'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->bisnisId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $data['total_karyawan'] = (int)$result['total_karyawan'];
            }
            
            // Query transaksi selesai
            $sql = "SELECT COUNT(*) as total_selesai
            FROM transaksi
            WHERE bisnis_id = ? AND status = 'selesai'" . $whereClause;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $data['total_selesai'] = (int)$result['total_selesai'];
                
                // Hitung rata-rata harian
                switch ($filterType) {
                    case 'hari_ini':
                        $data['rata_rata_harian'] = $result['total_selesai'];
                        break;
                    case '7_hari':
                        $data['rata_rata_harian'] = round($result['total_selesai'] / 7, 1);
                        break;
                    case 'bulan_ini':
                        $data['rata_rata_harian'] = round($result['total_selesai'] / date('j'), 1);
                        break;
                    default:
                        $data['rata_rata_harian'] = round($result['total_selesai'] / 30, 1);
                }
            }
            
            return $data;
            
        } catch (PDOException $e) {
            error_log('Error karyawan: ' . $e->getMessage());
            return ['total_karyawan' => 0, 'total_selesai' => 0, 'rata_rata_harian' => 0];
        }
    }
    
    /**
     * Query transaksi hari ini untuk rincian pendapatan
     */
    public function getTransaksiHariIni($limit = 4) {
        try {
            $sql = "SELECT t.no_nota, t.total_harga, t.status, t.created_at,
                   COALESCE(p.nama, 'Tanpa Nama') as nama_pelanggan,
                   CASE 
                       WHEN k.karyawan_id IS NOT NULL THEN u.nama_lengkap
                       ELSE 'Owner'
                   END as dibuat_oleh
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            LEFT JOIN karyawan k ON t.karyawan_id = k.karyawan_id
            LEFT JOIN users u ON k.user_id = u.user_id
            WHERE t.bisnis_id = ? AND DATE(t.created_at) = CURDATE()
            ORDER BY t.created_at DESC
            LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $this->bisnisId, PDO::PARAM_STR);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error transaksi hari ini: ' . $e->getMessage());
            return [];
        }
    }    
    /**
     * Query transaksi terakhir (semua periode) untuk ditampilkan di laporan
     */
    public function getTransaksiTerakhir($limit = 5) {
        try {
            $sql = "SELECT t.transaksi_id, t.no_nota, t.total_harga, t.status, t.created_at,
                   COALESCE(p.nama, 'Tanpa Nama') as nama_pelanggan,
                   CASE 
                       WHEN k.karyawan_id IS NOT NULL THEN u.nama_lengkap
                       ELSE 'Owner'
                   END as dibuat_oleh
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            LEFT JOIN karyawan k ON t.karyawan_id = k.karyawan_id
            LEFT JOIN users u ON k.user_id = u.user_id
            WHERE t.bisnis_id = ?
            ORDER BY t.created_at DESC
            LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $this->bisnisId, PDO::PARAM_STR);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error transaksi terakhir: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query semua transaksi dengan filter dan paginasi
     */
    public function getAllTransaksi($filterType = 'semua', $searchQuery = '', $tanggalMulai = null, $tanggalSelesai = null, $limit = 20, $offset = 0) {
        try {
            $whereConditions = ["t.bisnis_id = ?"];
            $params = [$this->bisnisId];
            
            // Filter status (tidak termasuk batal)
            $whereConditions[] = "t.status != 'batal'";
            
            // Filter berdasarkan tanggal
            if ($filterType != 'semua') {
                switch ($filterType) {
                    case 'hari_ini':
                        $whereConditions[] = "DATE(t.created_at) = CURDATE()";
                        break;
                    case '7_hari':
                        $whereConditions[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                        break;
                    case 'bulan_ini':
                        $whereConditions[] = "YEAR(t.created_at) = YEAR(CURDATE()) AND MONTH(t.created_at) = MONTH(CURDATE())";
                        break;
                    case 'kustom':
                        if ($tanggalMulai && $tanggalSelesai) {
                            $whereConditions[] = "DATE(t.created_at) BETWEEN ? AND ?";
                            $params[] = $tanggalMulai;
                            $params[] = $tanggalSelesai;
                        }
                        break;
                }
            }
            
            // Filter pencarian
            if (!empty($searchQuery)) {
                $whereConditions[] = "(t.no_nota LIKE ? OR p.nama LIKE ?)";
                $searchParam = '%' . $searchQuery . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT t.no_nota, t.total_harga, t.status, t.created_at,
                   COALESCE(p.nama, 'Tanpa Nama') as nama_pelanggan,
                   COALESCE(u.nama_lengkap, 'Sistem') as karyawan_nama
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            LEFT JOIN karyawan k ON t.karyawan_id = k.karyawan_id
            LEFT JOIN users u ON k.user_id = u.user_id
            {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error get all transaksi: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Hitung total transaksi untuk paginasi
     */
    public function getTotalTransaksi($filterType = 'semua', $searchQuery = '', $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereConditions = ["t.bisnis_id = ?"];
            $params = [$this->bisnisId];
            
            // Filter status (tidak termasuk batal)
            $whereConditions[] = "t.status != 'batal'";
            
            // Filter berdasarkan tanggal
            if ($filterType != 'semua') {
                switch ($filterType) {
                    case 'hari_ini':
                        $whereConditions[] = "DATE(t.created_at) = CURDATE()";
                        break;
                    case '7_hari':
                        $whereConditions[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                        break;
                    case 'bulan_ini':
                        $whereConditions[] = "YEAR(t.created_at) = YEAR(CURDATE()) AND MONTH(t.created_at) = MONTH(CURDATE())";
                        break;
                    case 'kustom':
                        if ($tanggalMulai && $tanggalSelesai) {
                            $whereConditions[] = "DATE(t.created_at) BETWEEN ? AND ?";
                            $params[] = $tanggalMulai;
                            $params[] = $tanggalSelesai;
                        }
                        break;
                }
            }
            
            // Filter pencarian
            if (!empty($searchQuery)) {
                $whereConditions[] = "(t.no_nota LIKE ? OR p.nama LIKE ?)";
                $searchParam = '%' . $searchQuery . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "SELECT COUNT(*) as total
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            {$whereClause}";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log('Error get total transaksi: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Query kinerja karyawan
     */
    public function getKinerjKaryawan($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        try {
            $whereClause = $this->getWhereClause($filterType, $tanggalMulai, $tanggalSelesai);
            $whereClauseKaryawan = str_replace('created_at', 't.created_at', $whereClause);
            $params = $this->getParams($filterType, $tanggalMulai, $tanggalSelesai);
            
            $sql = "SELECT u.nama_lengkap, COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as transaksi_selesai
            FROM karyawan k
            JOIN users u ON k.user_id = u.user_id
            LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id AND t.bisnis_id = k.bisnis_id" . $whereClauseKaryawan . "
            WHERE k.bisnis_id = ? AND k.status = 'aktif'
            GROUP BY k.karyawan_id, u.nama_lengkap
            ORDER BY transaksi_selesai DESC
            LIMIT 5";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log('Error kinerja karyawan: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Method untuk mendapatkan semua data laporan sekaligus
     */
    public function getAllData($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
        return [
            'pendapatan' => $this->getPendapatanData($filterType, $tanggalMulai, $tanggalSelesai),
            'layanan_terlaris' => $this->getLayananTerlaris($filterType, $tanggalMulai, $tanggalSelesai),
            'rincian_transaksi' => $this->getRincianTransaksi($filterType, $tanggalMulai, $tanggalSelesai),
            'pengeluaran' => $this->getPengeluaranData($filterType, $tanggalMulai, $tanggalSelesai),
            'kategori_pengeluaran' => $this->getKategoriPengeluaran($filterType, $tanggalMulai, $tanggalSelesai),
            'pengeluaran_terbaru' => $this->getPengeluaranTerbaru($filterType, $tanggalMulai, $tanggalSelesai),
            'pelanggan' => $this->getPelangganData($filterType, $tanggalMulai, $tanggalSelesai),
            'pelanggan_teratas' => $this->getPelangganTeratas($filterType, $tanggalMulai, $tanggalSelesai),
            'karyawan' => $this->getKaryawanData($filterType, $tanggalMulai, $tanggalSelesai),
            'kinerja_karyawan' => $this->getKinerjKaryawan($filterType, $tanggalMulai, $tanggalSelesai),
        ];
    }
}