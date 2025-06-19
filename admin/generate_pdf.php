<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../costumer_login.php");
    exit();
}

require_once '../vendor/autoload.php';
include '../config/config.php';

// Import TCPDF dengan namespace yang benar
use TCPDF as TCPDF;

// Cek apakah TCPDF tersedia
if (!class_exists('TCPDF')) {
    die('TCPDF library tidak ditemukan. Pastikan Anda sudah menjalankan composer install.');
}

// Get report type from URL parameter
$report_type = $_GET['type'] ?? 'all';

// Initialize TCPDF dengan konfigurasi yang benar
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Admin Panel');
$pdf->SetAuthor('System Administrator');
$pdf->SetTitle('Laporan Pesanan');
$pdf->SetSubject('Order Report');

// Set margins
$pdf->SetMargins(15, 27, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Calculate revenue metrics (sama seperti kode asli)
$current_month = date('Y-m');
$current_week = date('Y-W');
$current_day = date('Y-m-d');
$last_month = date('Y-m', strtotime('-1 month'));

// Get revenue data dengan error handling
try {
    $total_sql = "SELECT SUM(price_222060) AS total_revenue FROM order_report_222060 WHERE status_222060 = 'completed' AND price_222060 > 0";
    $total_query = $conn->query($total_sql);
    if (!$total_query) throw new Exception("Error fetching total revenue: " . $conn->error);
    $total_data = $total_query->fetch_assoc();
    $total_revenue = $total_data['total_revenue'] ?? 0;

    $monthly_sql = "SELECT SUM(price_222060) AS total_revenue FROM order_report_222060 WHERE DATE_FORMAT(completion_date_222060, '%Y-%m') = '$current_month' AND status_222060 = 'completed' AND price_222060 > 0";
    $monthly_query = $conn->query($monthly_sql);
    if (!$monthly_query) throw new Exception("Error fetching monthly revenue: " . $conn->error);
    $monthly_data = $monthly_query->fetch_assoc();
    $monthly_revenue = $monthly_data['total_revenue'] ?? 0;

    $weekly_sql = "SELECT SUM(price_222060) AS total_revenue FROM order_report_222060 WHERE DATE_FORMAT(completion_date_222060, '%Y-%u') = '$current_week' AND status_222060 = 'completed' AND price_222060 > 0";
    $weekly_query = $conn->query($weekly_sql);
    if (!$weekly_query) throw new Exception("Error fetching weekly revenue: " . $conn->error);
    $weekly_data = $weekly_query->fetch_assoc();
    $weekly_revenue = $weekly_data['total_revenue'] ?? 0;

    $daily_sql = "SELECT SUM(price_222060) AS total_revenue FROM order_report_222060 WHERE DATE(completion_date_222060) = '$current_day' AND status_222060 = 'completed' AND price_222060 > 0";
    $daily_query = $conn->query($daily_sql);
    if (!$daily_query) throw new Exception("Error fetching daily revenue: " . $conn->error);
    $daily_data = $daily_query->fetch_assoc();
    $daily_revenue = $daily_data['total_revenue'] ?? 0;

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Header
$html = '<h1 style="text-align: center; color: #343a40; margin-bottom: 20px;">LAPORAN PESANAN TOKO BANGUNAN</h1>';
$html .= '<p style="text-align: center; color: #666; margin-bottom: 30px;">Tanggal Cetak: ' . date('d F Y, H:i') . ' WIB</p>';

// Revenue Summary (always included)
if ($report_type == 'summary' || $report_type == 'all') {
    $html .= '<h2 style="color: #343a40; border-bottom: 2px solid #343a40; padding-bottom: 5px;">RINGKASAN PENDAPATAN</h2>';
    $html .= '<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; margin-bottom: 30px;">';
    $html .= '<tr style="background-color: #f8f9fa;">
                <th style="width: 40%; text-align: left; font-weight: bold;">Kategori</th>
                <th style="width: 60%; text-align: right; font-weight: bold;">Jumlah (Rp)</th>
              </tr>';
    $html .= '<tr><td>Total Pendapatan Keseluruhan</td><td style="text-align: right; font-weight: bold; color: #28a745;">Rp ' . number_format($total_revenue, 0, ',', '.') . '</td></tr>';
    $html .= '<tr><td>Pendapatan Bulan Ini (' . date('F Y') . ')</td><td style="text-align: right;">Rp ' . number_format($monthly_revenue, 0, ',', '.') . '</td></tr>';
    $html .= '<tr><td>Pendapatan Minggu Ini</td><td style="text-align: right;">Rp ' . number_format($weekly_revenue, 0, ',', '.') . '</td></tr>';
    $html .= '<tr><td>Pendapatan Hari Ini (' . date('d F Y') . ')</td><td style="text-align: right;">Rp ' . number_format($daily_revenue, 0, ',', '.') . '</td></tr>';
    $html .= '</table>';
}

// Generate detailed reports based on type
switch ($report_type) {
    case 'current_month':
        $html .= generateMonthlyReport($conn, $current_month, 'Bulan Ini (' . date('F Y') . ')');
        $filename = 'Laporan_Bulan_Ini_' . date('Y-m') . '.pdf';
        break;
        
    case 'last_month':
        $html .= generateMonthlyReport($conn, $last_month, 'Bulan Lalu (' . date('F Y', strtotime('-1 month')) . ')');
        $filename = 'Laporan_Bulan_Lalu_' . date('Y-m', strtotime('-1 month')) . '.pdf';
        break;
        
    case 'older':
        $html .= generateOlderReports($conn, $last_month);
        $filename = 'Laporan_Sebelumnya_' . date('Y-m-d') . '.pdf';
        break;
        
    case 'summary':
        $filename = 'Ringkasan_Revenue_' . date('Y-m-d') . '.pdf';
        break;
        
    case 'all':
    default:
        $html .= generateMonthlyReport($conn, $current_month, 'Bulan Ini (' . date('F Y') . ')');
        $html .= generateMonthlyReport($conn, $last_month, 'Bulan Lalu (' . date('F Y', strtotime('-1 month')) . ')');
        $html .= generateOlderReports($conn, $last_month);
        $filename = 'Laporan_Lengkap_' . date('Y-m-d') . '.pdf';
        break;
}

// Add footer
$html .= '<div style="margin-top: 40px; text-align: center; color: #666; font-size: 9px;">';
$html .= '<p>Dokumen ini dibuat secara otomatis oleh sistem</p>';
$html .= '<p>&copy; ' . date('Y') . ' Toko Bangunan. All rights reserved.</p>';
$html .= '</div>';

// Write HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output($filename, 'D');

// FUNGSI HELPER YANG DIPERBAIKI
function generateMonthlyReport($conn, $month, $title) {
    try {
        $sql = "SELECT r.*, p.product_image_222060 
                FROM order_report_222060 r
                LEFT JOIN order_222060 o ON r.order_id_222060 = o.id_222060
                LEFT JOIN product_222060 p ON r.product_222060 = p.product_name_222060
                WHERE DATE_FORMAT(r.completion_date_222060, '%Y-%m') = ?
                ORDER BY r.completion_date_222060 DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("s", $month);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        
        $result = $stmt->get_result();
        
    } catch (Exception $e) {
        return '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
    }
    
    $html = '<h2 style="color: #343a40; border-bottom: 2px solid #343a40; padding-bottom: 5px; margin-top: 30px;">' . strtoupper($title) . '</h2>';
    
    if ($result && $result->num_rows > 0) {
        // Calculate monthly totals
        $total_completed = 0;
        $total_cancelled = 0;
        $count_completed = 0;
        $count_cancelled = 0;
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            if ($row['status_222060'] == 'completed') {
                $total_completed += $row['price_222060'];
                $count_completed++;
            } else {
                $total_cancelled += $row['price_222060'];
                $count_cancelled++;
            }
        }
        
        // Summary for this month
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%; margin-bottom: 20px;">';
        $html .= '<tr style="background-color: #e9ecef;">
                    <th colspan="4" style="text-align: center; font-weight: bold;">RINGKASAN ' . strtoupper($title) . '</th>
                  </tr>';
        $html .= '<tr>
                    <td style="width: 25%; font-weight: bold;">Pesanan Selesai</td>
                    <td style="width: 25%;">' . $count_completed . ' pesanan</td>
                    <td style="width: 25%; font-weight: bold;">Pesanan Dibatalkan</td>
                    <td style="width: 25%;">' . $count_cancelled . ' pesanan</td>
                  </tr>';
        $html .= '<tr>
                    <td style="font-weight: bold; color: #28a745;">Revenue Selesai</td>
                    <td style="color: #28a745; font-weight: bold;">Rp ' . number_format($total_completed, 0, ',', '.') . '</td>
                    <td style="font-weight: bold; color: #dc3545;">Revenue Dibatalkan</td>
                    <td style="color: #dc3545;">Rp ' . number_format($total_cancelled, 0, ',', '.') . '</td>
                  </tr>';
        $html .= '</table>';
        
        // Detailed table
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; font-size: 9px;">';
        $html .= '<tr style="background-color: #f8f9fa;">
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 15%;">Pelanggan</th>
                    <th style="width: 25%;">Produk</th>
                    <th style="width: 8%; text-align: center;">Jumlah</th>
                    <th style="width: 15%; text-align: right;">Harga</th>
                    <th style="width: 12%; text-align: center;">Status</th>
                    <th style="width: 12%;">Tanggal</th>
                    <th style="width: 8%;">Catatan</th>
                  </tr>';
        
        $no = 1;
        foreach ($rows as $row) {
            $status_color = ($row['status_222060'] == 'completed') ? '#28a745' : '#dc3545';
            $html .= '<tr>';
            $html .= '<td style="text-align: center;">' . $no++ . '</td>';
            $html .= '<td>' . htmlspecialchars($row['customer_name_222060']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['product_222060']) . '</td>';
            $html .= '<td style="text-align: center;">' . htmlspecialchars($row['quantity_222060']) . '</td>';
            $html .= '<td style="text-align: right;">Rp ' . number_format($row['price_222060'], 0, ',', '.') . '</td>';
            $html .= '<td style="text-align: center; color: ' . $status_color . '; font-weight: bold;">' . htmlspecialchars($row['status_222060']) . '</td>';
            $html .= '<td>' . date('d/m/Y', strtotime($row['completion_date_222060'])) . '</td>';
            $html .= '<td style="font-size: 8px;">' . htmlspecialchars(substr($row['notes_222060'], 0, 20)) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    } else {
        $html .= '<p style="text-align: center; color: #666; font-style: italic; padding: 20px;">Tidak ada data laporan untuk periode ini</p>';
    }
    
    return $html;
}

function generateOlderReports($conn, $last_month) {
    try {
        $sql = "SELECT r.*, p.product_image_222060 
                FROM order_report_222060 r
                LEFT JOIN order_222060 o ON r.order_id_222060 = o.id_222060
                LEFT JOIN product_222060 p ON r.product_222060 = p.product_name_222060
                WHERE DATE_FORMAT(r.completion_date_222060, '%Y-%m') < ?
                ORDER BY r.completion_date_222060 DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("s", $last_month);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        
        $result = $stmt->get_result();
        
    } catch (Exception $e) {
        return '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
    }
    
    $html = '<h2 style="color: #343a40; border-bottom: 2px solid #343a40; padding-bottom: 5px; margin-top: 30px;">LAPORAN SEBELUMNYA</h2>';
    
    if ($result && $result->num_rows > 0) {
        // Group by month for older reports
        $monthly_data = [];
        while ($row = $result->fetch_assoc()) {
            $month_key = date('Y-m', strtotime($row['completion_date_222060']));
            $monthly_data[$month_key][] = $row;
        }
        
        foreach ($monthly_data as $month => $orders) {
            $month_name = date('F Y', strtotime($month . '-01'));
            $html .= '<h3 style="color: #495057; margin-top: 20px; margin-bottom: 10px;">' . $month_name . '</h3>';
            
            $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width: 100%; font-size: 9px; margin-bottom: 15px;">';
            $html .= '<tr style="background-color: #f8f9fa;">
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Pelanggan</th>
                        <th style="width: 25%;">Produk</th>
                        <th style="width: 8%;">Jumlah</th>
                        <th style="width: 15%;">Harga</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 12%;">Tanggal</th>
                        <th style="width: 8%;">Catatan</th>
                      </tr>';
            
            $no = 1;
            foreach ($orders as $row) {
                $status_color = ($row['status_222060'] == 'completed') ? '#28a745' : '#dc3545';
                $html .= '<tr>';
                $html .= '<td style="text-align: center;">' . $no++ . '</td>';
                $html .= '<td>' . htmlspecialchars($row['customer_name_222060']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['product_222060']) . '</td>';
                $html .= '<td style="text-align: center;">' . htmlspecialchars($row['quantity_222060']) . '</td>';
                $html .= '<td style="text-align: right;">Rp ' . number_format($row['price_222060'], 0, ',', '.') . '</td>';
                $html .= '<td style="text-align: center; color: ' . $status_color . ';">' . htmlspecialchars($row['status_222060']) . '</td>';
                $html .= '<td>' . date('d/m/Y', strtotime($row['completion_date_222060'])) . '</td>';
                $html .= '<td style="font-size: 8px;">' . htmlspecialchars(substr($row['notes_222060'], 0, 15)) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
    } else {
        $html .= '<p style="text-align: center; color: #666; font-style: italic; padding: 20px;">Tidak ada data laporan sebelumnya</p>';
    }
    
    return $html;
}
?>
