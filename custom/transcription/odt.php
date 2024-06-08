<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generate ODT File</title>
</head>
<body>
  <h1>Generate ODT Document</h1>
  <?php
  $LabNumber = $_GET['lab_number']; 
  ?>
 
  <button id="generateOdtButton">Generate ODT</button>

  <script>
    document.getElementById('generateOdtButton').addEventListener('click', function() {
      const labNumber = '<?php echo $LabNumber; ?>'; // Access lab number from PHP
      const fileName = `${labNumber}_generated_document.odt`; // Construct dynamic filename

      fetch(`generate_odt.php?lab_number=${labNumber}`) // Pass lab number as query parameter
        .then(response => response.blob())
        .then(blob => {
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = fileName; // Set dynamic download filename
          a.click();
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          console.error(error);
        });
    });
  </script>
</body>
</html>