<?php
// 1. Connection File Include Karein
include 'config/db.php'; // Apne folder ke mutabik check kar lena (e.g., 'db.php' ya 'config/db.php')
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php?error=please_login");
    exit();
}
$user_id = $_SESSION['user_id'];
$pat_query = "Select patient_id From patients WHERE user_id = '$user_id'";
$pat_result = mysqli_query($conn,$pat_query);
if(mysqli_num_rows($pat_result) > 0){
    $row = mysqli_fetch_assoc($pat_result);
    $pati_id = $row['patient_id'];
}

// 2. Get Doctor ID from URL (Default to 5 for testing)
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 5;
$patient_id = $pati_id; // Testing ke liye dummy Patient ID (Jab login banaoge to $_SESSION['user_id'] se badal dena)

$msg = ""; // Success ya Error message ke liye variable

// =======================================================
// 📥 3. FORM PROCESS LOGIC (Isi page par data handle hoga)
// =======================================================
if (isset($_POST['book_appointment_btn'])) {
    
    $doc_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $pat_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $app_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $app_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // Dropdown value se clinic_id alag kar rahe hain (Kyunki humne value me clinic_id pass ki thi)
    $clinic_id = intval($_POST['clinic_id']);

    // Insert Query (Saare columns jo tumne bataye)
    $insert_query = "INSERT INTO appointments 
                    (doctor_id, patient_id, clinic_id, appointment_date, appointment_time, reason, notes, created_at, updated_at) 
                    VALUES 
                    ('$doc_id', '$patient_id', '$clinic_id', '$app_date', '$app_time', '$reason', '$notes', NOW(), NOW())";

    if (mysqli_query($conn, $insert_query)) {
        $msg = "<div style='padding:15px; background-color:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:5px; margin-bottom:20px;'>
                    <strong>Zabardast!</strong> Appointment successfully book ho gayi hai. Doctor ki approval ka intezar karein.
                </div>";
    } else {
        $msg = "<div style='padding:15px; background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px; margin-bottom:20px;'>
                    <strong>Error:</strong> Data insert nahi ho saka: " . mysqli_error($conn) . "
                </div>";
    }
}

// =======================================================
// 🔍 4. FETCH DATA FOR FORM (Clinics aur Availability Joina)
// =======================================================
// Is query se hum check kar rahe hain ke doctor kis clinic me baithta hai aur uski timing kya hai
$query = "SELECT dc.clinic_id, c.clinic_name, da.day, da.start_time, da.end_time, da.slot_duration 
          FROM doctor_clinic dc
          JOIN clinics c ON dc.clinic_id = c.clinic_id
          JOIN doctor_availability da ON dc.doctor_id = da.doctor_id
          WHERE dc.doctor_id = '$doctor_id'";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Details & Booking</title>
</head>
<body>

    <div style="max-width: 600px; margin: 30px auto; font-family: Arial, sans-serif;">
        
        <!-- Msg Alert Box (Success/Error) -->
        <?php echo $msg; ?>

        <fieldset style="padding: 20px; border-radius: 8px; border: 2px solid #333;">
            <legend style="font-size: 1.2rem; font-weight: bold; padding: 0 10px;">Book an Appointment</legend>
            
            <!-- action empty "" chora hai taake data isi page par refresh ho kar submit ho -->
            <form action="" method="POST">
                
                <!-- Hidden Fields (Parde ke peeche ka data) -->
                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">

                <!-- 1. SELECT CLINIC -->
                <p>
                    <label><strong>Select Clinic & Day:</strong></label><br>
                    <select name="clinic_id" style="width: 100%; padding: 8px; margin-top: 5px;" required>
                        <option value="">-- Choose Clinic & Available Day --</option>
                        <?php 
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $readable_start = date("h:i A", strtotime($row['start_time']));
                                $readable_end = date("h:i A", strtotime($row['end_time']));
                                
                                // Value me clinic_id ja rahi hai aur text me poori detail dikhegi
                                echo "<option value='".$row['clinic_id']."'>".$row['clinic_name']." (".$row['day']." : $readable_start - $readable_end)</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No clinics linked or timings set for this doctor.</option>";
                        }
                        ?>
                    </select>
                </p>

                <!-- 2. APPOINTMENT DATE -->
                <p>
                    <label><strong>Select Date:</strong></label><br>
                    <input type="date" name="appointment_date" style="width: 97%; padding: 8px; margin-top: 5px;" min="<?php echo date('Y-m-d'); ?>" required>
                </p>

                <!-- 3. DYNAMIC TIME SLOTS BREAKDOWN -->
                <p>
                    <label><strong>Available Time Slots (15/20/30 Mins):</strong></label><br>
                    <select name="appointment_time" style="width: 100%; padding: 8px; margin-top: 5px;" required>
                        <option value="">-- Select a Time Slot --</option>
                        
                        <?php
                        // Database pointer reset taake dobara loop chal sake slots ke liye
                        if (mysqli_num_rows($result) > 0) {
                            mysqli_data_seek($result, 0);
                            
                            while ($sched = mysqli_fetch_assoc($result)) {
                                $current_pointer = strtotime($sched['start_time']);
                                $closing_pointer = strtotime($sched['end_time']);
                                $slot_seconds = $sched['slot_duration'] * 60; // Minutes to seconds

                                while ($current_pointer < $closing_pointer) {
                                    $slot_start = date("h:i A", $current_pointer);
                                    $next_pointer = $current_pointer + $slot_seconds;
                                    
                                    if ($next_pointer > $closing_pointer) {
                                        break;
                                    }
                                    
                                    $slot_end = date("h:i A", $next_pointer);
                                    $full_slot_text = "$slot_start - $slot_end";

                                    echo "<option value='$full_slot_text'>$full_slot_text</option>";
                                    
                                    $current_pointer = $next_pointer; 
                                }
                            }
                        }
                        ?>
                    </select>
                </p>

                <!-- 4. REASON -->
                <p>
                    <label><strong>Reason for Visit (Symptoms):</strong></label><br>
                    <input type="text" name="reason" placeholder="e.g., Stomach pain, Fever" style="width: 97%; padding: 8px; margin-top: 5px;" required>
                </p>

                <!-- 5. PATIENT NOTES (Extra info) -->
                <p>
                    <label><strong>Additional Notes (Optional):</strong></label><br>
                    <textarea name="notes" rows="3" placeholder="Any previous medical history or detail..." style="width: 97%; padding: 8px; margin-top: 5px;"></textarea>
                </p>

                <!-- SUBMIT BUTTON -->
                <p style="margin-top: 20px;">
                    <button type="submit" name="book_appointment_btn" style="width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; font-weight: bold;">
                        Confirm & Book Appointment
                    </button>
                </p>

            </form>
        </fieldset>
    </div>

</body>
</html>