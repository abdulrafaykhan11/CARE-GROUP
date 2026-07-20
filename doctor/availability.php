<?php
include "../config/db.php";

// Testing ke liye doctor ID URL se uthao (?doc=5), agar nahi hai to default 5
$doctor_id = isset($_GET['doc']) ? intval($_GET['doc']) : 5;

$msg = ""; // Message store karne ke liye variable

if (isset($_POST['add_schedule_btn'])) {
    $day = mysqli_real_escape_string($conn, $_POST['available_day']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $slot_duration = intval($_POST['slot_duration']);

    $min_allowed = strtotime("09:00");
    $max_allowed = strtotime("23:00");

    $user_start = strtotime($start_time);
    $user_end = strtotime($end_time);

    // --- VALIDATION CHECKS ---
    if (($user_end - $user_start) < 3600) {
        $msg = "<p style='color:red;'>Minimum duration must be 1 hour!</p>";
    } 
    // Yahan saaf less than (<) aur greater than (>) laga diya taake dot 9 baje aur 11 baje block na ho
    elseif ($user_start < $min_allowed || $user_start > $max_allowed) {
        $msg = "<p style='color:red;'>Wrong Start Time! Clinic starts at 09:00 AM and ends at 11:00 PM (23:00).</p>";
    } 
    elseif ($user_end < $min_allowed || $user_end > $max_allowed) {
        $msg = "<p style='color:red;'>Wrong End Time! Clinic starts at 09:00 AM and ends at 11:00 PM (23:00).</p>";
    } 
    elseif ($user_start >= $user_end) {
        $msg = "<p style='color:red;'>Logic Error: Start time must be before End time!</p>";
    } 
    else {
        // SQL query tumhare table structure ke mutabik (doctor_availability)
        $query = "INSERT INTO doctor_availability (day, start_time, end_time, slot_duration, doctor_id) 
                  VALUES ('$day', '$start_time', '$end_time', '$slot_duration', '$doctor_id')";
                  
        $result = mysqli_query($conn, $query);
        if ($result) {
            $msg = "<p style='color:green;'>Time slot added successfully for Doctor ID: $doctor_id</p>";
        } else {
            $msg = "<p style='color:red;'>Error adding time slot: " . mysqli_error($conn) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability</title>
</head>
<body>

    <!-- Yahan error ya success message show hoga -->
    <?php echo $msg; ?>

    <fieldset>
        <legend>Add New Time Slot (Doctor ID: <?php echo $doctor_id; ?>)</legend>
        <!-- Action ko khaali chora taake isi dynamic URL par post ho -->
        <form action="" method="POST">
            <label>Select Day:</label>
            <select name="available_day" required>
                <option value="">-- Select Day --</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
            </select>

            <label>Start Time:</label>
            <input type="time" name="start_time" min="09:00" max="23:00" required>

            <label>End Time:</label>
            <input type="time" name="end_time" min="09:00" max="23:00" required>

            <label>Slot Duration:</label>
            <select name="slot_duration" required>
                <option value="">Select Duration</option>
                <option value="15">15 Minutes</option>
                <option value="20">20 Minutes</option>
                <option value="30">30 Minutes</option>
            </select>

            <button type="submit" name="add_schedule_btn">Add Slot</button>
        </form>
    </fieldset>

</body>
</html>