<form action="upload_csv.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="campaign_id" value="1">

    <label>Select CSV File</label><br>
    <input type="file" name="csv_file" accept=".csv" required><br><br>

    <button type="submit">Upload & Queue Emails</button>
</form>
