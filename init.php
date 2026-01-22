<?php

require_once 'FeederWS.php';
require_once 'Logger.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="list_mahasiswa.csv"');

try {
    $startTime = microtime(true);
    $feeder = new FeederWS();

    $ts = (date('Y') -5) . '1';
    $glmFilter = "id_periode >= '$ts'";
    
    $feeder->logger->info('Starting CSV export', ['filter' => $glmFilter]);
    
    // Ambil semua data dengan pagination
    $allData = [];
    $offset = 0;
    $limit = 100;
    $batchCount = 0;
    
    do {
        $batch = $feeder->GetListMahasiswa($glmFilter, $limit, $offset);
        
        if (!empty($batch)) {
            $allData = array_merge($allData, $batch);
            $batchCount++;
            $offset += $limit;
            $feeder->logger->debug("Batch #$batchCount fetched", ['records' => count($batch), 'total_so_far' => count($allData)]);
        }
        
    } while (!empty($batch) && count($batch) == $limit);
    
    $output = fopen('php://output','w');

    if (!empty($allData)) {
        $header = array_keys($allData[0]);
    
        fputcsv($output, $header);
        foreach($allData as $row){
            fputcsv($output, $row);
        }
        
        $duration = round(microtime(true) - $startTime, 2);
        $feeder->logger->info('CSV export completed successfully', [
            'total_records' => count($allData),
            'batches' => $batchCount,
            'duration_seconds' => $duration
        ]);
        
    } else{
        fputcsv($output, ['status' => 'Data Kosong']);
        $feeder->logger->warning('No data found for export');
    }
    
    fclose($output);
    exit;
    
}catch(Exception $e){
    // Log error safely - $feeder might not exist if exception occurred during initialization
    if (isset($feeder)) {
        $feeder->logger->error('Export failed', ['error' => $e->getMessage()]);
    } else {
        // Fallback logger if $feeder hasn't been created yet
        $logger = new Logger();
        $logger->error('Export failed before FeederWS initialization', ['error' => $e->getMessage()]);
    }
    echo 'Error: ' . $e->getMessage();
}