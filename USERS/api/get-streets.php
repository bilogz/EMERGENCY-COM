<?php
/**
 * Get Quezon City Streets API
 * Returns list of common Quezon City streets for address selection
 */

header('Content-Type: application/json');

// Common Quezon City streets
$streets = [
    // Major Roads
    "EDSA (Epifanio de los Santos Avenue)",
    "Quezon Avenue",
    "Commonwealth Avenue",
    "Aurora Boulevard",
    "España Boulevard",
    "Roosevelt Avenue",
    "Timog Avenue",
    "East Avenue",
    "West Avenue",
    "North Avenue",
    "Visayas Avenue",
    "Mindanao Avenue",
    "Katipunan Avenue",
    "Tandang Sora Avenue",
    "Congressional Avenue",
    "Quirino Highway",
    "Regalado Avenue",
    "Novaliches Avenue",
    "Fairview Avenue",
    
    // Streets in Diliman Area
    "Maginhawa Street",
    "Matalino Street",
    "Roces Avenue",
    "Kamias Road",
    "Kamuning Road",
    "Scout Area Streets",
    "Scout Rallos Street",
    "Scout Torillo Street",
    "Scout Fernandez Street",
    "Scout Reyes Street",
    "Scout Delgado Street",
    "Scout Borromeo Street",
    "Scout Albano Street",
    "Scout Chuatoco Street",
    "Scout Fuentebella Street",
    "Scout Limbaga Street",
    "Scout Tobias Street",
    "Scout Ybardolaza Street",
    "Scout Castor Street",
    "Scout Tuazon Street",
    
    // Streets in Project Areas
    "Project 1 Streets",
    "Project 2 Streets",
    "Project 3 Streets",
    "Project 4 Streets",
    "Project 5 Streets",
    "Project 6 Streets",
    "Project 7 Streets",
    "Project 8 Streets",
    
    // Streets in Cubao Area
    "General Romulo Avenue",
    "P. Tuazon Boulevard",
    "15th Avenue",
    "20th Avenue",
    "Araneta Avenue",
    "Cordillera Street",
    "Manila Avenue",
    "New York Street",
    "Connecticut Street",
    "Massachusetts Street",
    
    // Streets in Kamuning Area
    "11th Jamboree Street",
    "K-1st Street",
    "K-2nd Street",
    "K-3rd Street",
    "K-4th Street",
    "K-5th Street",
    "K-6th Street",
    "K-7th Street",
    "K-8th Street",
    "K-9th Street",
    "K-10th Street",
    
    // Streets in Loyola Heights
    "F. dela Rosa Street",
    "Esteban Abada Street",
    "Matahimik Street",
    "Malingap Street",
    "Magiting Street",
    "Malakas Street",
    "Mabuhay Street",
    "Masagana Street",
    "Masikap Street",
    "Matatag Street",
    
    // Streets in New Manila
    "E. Rodriguez Sr. Avenue",
    "Gilmore Avenue",
    "N. Domingo Street",
    "Banawe Street",
    "D. Tuazon Street",
    "Sto. Domingo Avenue",
    "Doña Hemady Avenue",
    "St. Joseph Street",
    "St. Mary Street",
    "St. Paul Street",
    
    // Streets in San Francisco del Monte
    "Del Monte Avenue",
    "Banawe Street Extension",
    "Sto. Cristo Street",
    "Masambong Street",
    "Talayan Street",
    "Damayan Street",
    "Damar Street",
    
    // Streets in Fairview
    "Regalado Avenue",
    "Commonwealth Avenue Extension",
    "Maligaya Street",
    "Malaya Street",
    "Malinis Street",
    
    // Streets in Novaliches
    "Quirino Highway",
    "Novaliches Bayan",
    "Susano Road",
    "Zabarte Road",
    "Mindanao Avenue Extension",
    
    // Other Common Streets
    "Abad Santos Avenue",
    "A. Bonifacio Avenue",
    "Blumentritt Street",
    "Boni Avenue",
    "Boni Serrano Avenue",
    "C. Benitez Street",
    "Dapitan Street",
    "Don Alejandro Roces Avenue",
    "Don Mariano Marcos Avenue",
    "E. Rodriguez Avenue",
    "F. Blumentritt Street",
    "General Luis Street",
    "General Santos Avenue",
    "G. Araneta Avenue",
    "Ibañez Street",
    "J. P. Rizal Street",
    "Kalayaan Avenue",
    "Karuhatan Street",
    "Libertad Street",
    "Magsaysay Avenue",
    "Marcos Highway",
    "Mayon Street",
    "N. S. Amoranto Avenue",
    "Ortigas Avenue Extension",
    "P. Burgos Street",
    "P. Guevarra Street",
    "P. Noval Street",
    "P. Paredes Street",
    "P. Tuazon Street",
    "R. Magsaysay Boulevard",
    "R. Papa Street",
    "Retiro Street",
    "Rizal Avenue Extension",
    "Sampaloc Street",
    "San Juan Street",
    "Sgt. Esguerra Avenue",
    "Sgt. Rivera Street",
    "Sgt. Bumatay Street",
    "Sgt. Fabian Yabut Street",
    "Tomas Morato Avenue",
    "United Nations Avenue",
    "V. Luna Road",
    "V. Mapa Street",
    "V. Rufino Street",
    "Wilson Street",
    "Xavier Street",
    "Yale Street",
    "Zabarte Road"
];

// Remove duplicates and sort
$streets = array_unique($streets);
sort($streets);

echo json_encode([
    "success" => true,
    "streets" => array_values($streets),
    "count" => count($streets)
]);
?>

