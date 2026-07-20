<?php
include "../config/db.php";

// 1. Auth Check: Logged-in hai ya nahi?
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Logged-in user se doctor_id nikalo
$doc_query = "SELECT doctor_id FROM doctors WHERE user_id = '$user_id'";
$doc_result = mysqli_query($conn, $doc_query);

if ($doc_result && mysqli_num_rows($doc_result) > 0) {
    $doc_row = mysqli_fetch_assoc($doc_result);
    $doctor_id = $doc_row['doctor_id'];
} else {
    die("Error: Authorized doctor record not found.");
}

$msg = "";

// =======================================================
// 🔄 3. STATUS UPDATE LOGIC (Approve / Reject / Complete)
// =======================================================
if (isset($_POST['update_status_btn'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status     = mysqli_real_escape_string($conn, $_POST['status_value']);

    $update_query = "UPDATE appointments 
                     SET status = '$new_status', updated_at = NOW() 
                     WHERE appointment_id = '$appointment_id' AND doctor_id = '$doctor_id'";

    if (mysqli_query($conn, $update_query)) {
        $msg = "<p style='color: green; font-weight: bold;'>Appointment status updated to '$new_status'!</p>";
    } else {
        $msg = "<p style='color: red;'>Error updating status: " . mysqli_error($conn) . "</p>";
    }
}

// =======================================================
// 🔍 4. FETCH ALL APPOINTMENTS (With Filters)
// =======================================================
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$app_query = "SELECT 
                a.appointment_id,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                a.notes,
                u.full_name AS patient_name,
                c.clinic_name
              FROM appointments a
              JOIN patients p ON a.patient_id = p.patient_id
              JOIN users u ON p.user_id = u.user_id
              JOIN clinics c ON a.clinic_id = c.clinic_id
              WHERE a.doctor_id = '$doctor_id'";

// Agar user ne status filter select kiya ho
if (!empty($status_filter)) {
    $app_query .= " AND a.status = '$status_filter'";
}

$app_query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";
$app_result = mysqli_query($conn, $app_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Appointments</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; color: white; }
        .badge-Pending { background-color: #ffc107; color: black; }
        .badge-Confirmed { background-color: #28a745; }
        .badge-Cancelled { background-color: #dc3545; }
        .badge-Completed { background-color: #17a2b8; }
        .btn { padding: 5px 10px; border: none; cursor: pointer; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .btn-approve { background-color: #28a745; color: white; }
        .btn-cancel { background-color: #dc3545; color: white; }
        .btn-complete { background-color: #17a2b8; color: white; }
        .filter-box { margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Appointments</h2>
    
    <?php echo $msg; ?>

    <!-- STATUS FILTER -->
    <div class="filter-box">
        <strong>Filter by Status: </strong>
        <a href="appointments.php">All</a> | 
        <a href="appointments.php?status=Pending">Pending</a> | 
        <a href="appointments.php?status=Confirmed">Confirmed</a> | 
        <a href="appointments.php?status=Completed">Completed</a> | 
        <a href="appointments.php?status=Cancelled">Cancelled</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Patient Name</th>
                <th>Clinic</th>
                <th>Date & Time</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($app_result && mysqli_num_rows($app_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($app_result)): ?>
                    <tr>
                        <td>#<?php echo $row['appointment_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['patient_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['clinic_name']); ?></td>
                        <td>
                            <?php 
                                echo date("d M, Y", strtotime($row['appointment_date'])) . "<br>"; 
                                echo "<small style='color: #666;'>" . htmlspecialchars($row['appointment_time']) . "</small>";
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $row['status']; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td>
                            <!-- STATUS ACTION FORM -->
                            <form action="" method="POST" style="display:inline-block;">
                                <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                
                                <?php if ($row['status'] == 'Pending'): ?>
                                    <button type="submit" name="update_status_btn" value="submit" onclick="this.form.status_value.value='Confirmed'" class="btn btn-approve">Confirm</button>
                                    <button type="submit" name="update_status_btn" value="submit" onclick="this.form.status_value.value='Cancelled'" class="btn btn-cancel">Cancel</button>
                                <?php elseif ($row['status'] == 'Confirmed'): ?>
                                    <button type="submit" name="update_status_btn" value="submit" onclick="this.form.status_value.value='Completed'" class="btn btn-complete">Mark Completed</button>
                                    <button type="submit" name="update_status_btn" value="submit" onclick="this.form.status_value.value='Cancelled'" class="btn btn-cancel">Cancel</button>
                                <?php else: ?>
                                    <small style="color: #888;">No action</small>
                                <?php endif; ?>

                                <input type="hidden" name="status_value" value="">
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No appointments found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>