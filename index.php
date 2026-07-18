<?php
include "config/db.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Search Ecosystem</title>
</head>

<body>
    <a href="logout.php">Logout</a>
    <form action="" method="post">
        <!-- Text box search input pattern -->
        Search : <input type="search" name="search" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>" placeholder="Name, Specialization, City"><br><br>

        <!-- City Dropdown Structure -->
        City : <select name="city_name">
            <option value="">Select</option>
            <?php
            $cityQuery = "SELECT city_name FROM cities";
            $cityResult = mysqli_query($conn, $cityQuery);

            while ($city = mysqli_fetch_assoc($cityResult)) {
                $selected = (isset($_POST['city_name']) && $_POST['city_name'] === $city['city_name']) ? 'selected' : '';
                echo "<option value='" . $city['city_name'] . "' $selected>" . $city['city_name'] . "</option>";
            }
            ?>
        </select>

        <!-- Specialization Dropdown Structure -->
        Specialization : <select name="specialization_name">
            <option value="">Select</option>
            <?php
            $spec_query = "SELECT specialization_name FROM specializations";
            $spec_result = mysqli_query($conn, $spec_query);

            while ($spec = mysqli_fetch_assoc($spec_result)) {
                $selected = (isset($_POST['specialization_name']) && $_POST['specialization_name'] === $spec['specialization_name']) ? 'selected' : '';
                echo "<option value='" . $spec['specialization_name'] . "' $selected>" . $spec['specialization_name'] . "</option>";
            }
            ?>
        </select>

        <!-- Gender Filter Layout Selection -->
        GENDER : 
        <select name="gender">
            <option value="">Select</option>
            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
        </select><br><br>

        <input type="submit" name="submit" value="Apply Filters">
        <a href="index.php"><input type="button" value="Refresh / Clear"></a>
    </form>
</body>

</html>

<?php
// Base Query Structure setup layout
$query = "SELECT u.full_name, d.bio, s.specialization_name, c.city_name, d.experience_years, d.gender, d.qualification, d.profile_image, d.full_address 
          FROM doctors d 
          JOIN users u ON d.user_id = u.user_id 
          JOIN cities c ON d.city_id = c.city_id 
          JOIN specializations s ON d.specialization_id = s.specialization_id 
          WHERE 1=1";

// Saare filters is main submit check ke andar hone chahiyen
if (isset($_POST['submit'])) {
    
    // 1. Core Text Field Input Search Box
    if (!empty($_POST['search'])) {
        $search = mysqli_real_escape_string($conn, $_POST['search']);
        $query .= " AND (u.full_name LIKE '%$search%' OR s.specialization_name LIKE '%$search%' OR c.city_name LIKE '%$search%')";
    }
    
    // 2. City Filter Dropdown
    if (!empty($_POST['city_name'])) {
        $city_name = mysqli_real_escape_string($conn, $_POST['city_name']);
        $query .= " AND c.city_name = '$city_name'";
    }
    
    // 3. Specialization Filter Dropdown
    if (!empty($_POST['specialization_name'])) {
        $specialization_name = mysqli_real_escape_string($conn, $_POST['specialization_name']);
        $query .= " AND s.specialization_name = '$specialization_name'";
    }
    
    // 4. Gender Filter Selection Verification
    if (!empty($_POST['gender'])) {
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $query .= " AND d.gender = '$gender'";
    }
}

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<br><table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr bgcolor='#f2f2f2'>
        <td>FULL NAME</td>
        <td>DOCTOR'S BIO</td>
        <td>DOCTOR SPECIALIZATION</td>
        <td>CITY NAME</td>
        <td>EXPERIENCE YEARS</td>
        <td>GENDER</td>
        <td>DOCTOR QUALIFICATION</td>
        <td>PROFILE IMAGE</td>
        <td>CLINIC ADDRESS</td>
        </tr>";
        
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bio']) . "</td>";
        echo "<td>" . htmlspecialchars($row['specialization_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['city_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['experience_years']) . " Years</td>";
        echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
        echo "<td>" . htmlspecialchars($row['qualification']) . "</td>";
        echo "<td><img src='assets/uploads/doctor/profile/" . htmlspecialchars($row['profile_image']) . "' height='100' width='100' style='object-fit: contain;'></td>";
        echo "<td>" . htmlspecialchars($row['full_address']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<br><p>No doctors found matching the selected criteria.</p>";
}
?>