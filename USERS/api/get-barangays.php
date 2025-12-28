<?php
/**
 * Get Quezon City Barangays API
 * Returns list of all Quezon City barangays for address selection
 */

header('Content-Type: application/json');

$barangayFile = __DIR__ . '/../../../barangay-main/barangay/data/qc_barangays.json';

if (!file_exists($barangayFile)) {
    // Fallback to hardcoded list if file doesn't exist
    $barangays = [
        "Alicia", "Amihan", "Apolonio Samson", "Aurora", "Baesa", "Bagbag",
        "Bagong Lipunan Ng Crame", "Bagong Pag-asa", "Bagong Silangan", "Bagumbayan",
        "Bagumbuhay", "Bahay Toro", "Balingasa", "Balong Bato", "Batasan Hills",
        "Bayanihan", "Blue Ridge A", "Blue Ridge B", "Botocan", "Bungad",
        "Camp Aguinaldo", "Capri", "Central", "Claro", "Commonwealth", "Culiat",
        "Damar", "Damayan", "Damayang Lagi", "Del Monte", "Dioquino Zobel",
        "Don Manuel", "Doña Imelda", "Doña Josefa", "Duyan-duyan", "E. Rodriguez",
        "East Kamias", "Escopa I", "Escopa II", "Escopa III", "Escopa IV",
        "Fairview", "Greater Lagro", "Gulod", "Holy Spirit", "Horseshoe",
        "Immaculate Concepcion", "Kaligayahan", "Kalusugan", "Kamuning", "Katipunan",
        "Kaunlaran", "Kristong Hari", "Krus Na Ligas", "Laging Handa", "Libis",
        "Loyola Heights", "Maharlika", "Malaya", "Manresa", "Mariana", "Mariblo",
        "Marilag", "Masagana", "Masambong", "Matandang Balara", "Milagrosa",
        "Nagkaisang Nayon", "Nayong Kanluran", "New Era", "N.S Amoranto",
        "North Fairview", "Novaliches Proper", "Obrero", "Old Capitol Site",
        "Pag-ibig sa Nayon", "Paligsahan", "Paltok", "Pansol", "Paraiso",
        "Pasong Putik Proper", "Pasong Tamo", "Payatas", "Phil-Am", "Pinagkaisahan",
        "Pinyahan", "Project 6", "Quirino 2-A", "Quirino 2-B", "Quirino 2-C",
        "Quirino 3-A", "Ramon Magsaysay", "Roxas", "Sacred Heart", "Salvacion",
        "San Agustin", "San Antonio", "San Bartolome", "San Isidro Galas",
        "San Isidro Labrador", "San Jose", "San Martin De Porres", "San Roque",
        "San Vicente", "Sangandaan", "Santa Cruz", "Santa Lucia", "Santa Monica",
        "Santa Teresita", "Sauyo", "Siena", "Silangan", "Sikatuna Village",
        "Socorro", "South Triangle", "St. Ignatius", "St. Peter", "Sto. Cristo",
        "Sto. Domingo", "Sto. Niño", "Tagumpay", "Talayan", "Talipapa", "Tandang Sora",
        "Tatalon", "Teachers Village East", "Teachers Village West", "Tandang Sora",
        "Ugong Norte", "Unang Sigaw", "UP Campus", "UP Village", "Valencia",
        "Vasra", "Veterans Village", "Villa Maria Clara", "West Kamias", "West Triangle",
        "White Plains"
    ];
} else {
    $barangays = json_decode(file_get_contents($barangayFile), true);
    if (!is_array($barangays)) {
        $barangays = [];
    }
}

// Sort alphabetically
sort($barangays);

echo json_encode([
    "success" => true,
    "barangays" => $barangays,
    "count" => count($barangays)
]);
?>

