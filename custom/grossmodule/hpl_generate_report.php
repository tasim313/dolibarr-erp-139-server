<?php
include('connection.php');
include('gross_common_function.php');

if (isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $ssl = 'https';
}
else {
  $ssl = 'http';
}
 
$app_url = ($ssl  )
          . "://".$_SERVER['HTTP_HOST']
          . (dirname($_SERVER["SCRIPT_NAME"]) == DIRECTORY_SEPARATOR ? "" : "/")
          . trim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");


header("Access-Control-Allow-Origin: *");

?>

<!DOCTYPE html>
<html>
<head>
	 
	<title> HISTOPATHOLOGY REPORT </title>

	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="description" content="This ">

	<meta name="author" content="Code With Mark">
	<meta name="authorUrl" content="http://codewithmark.com">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">


	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js"></script> 


	<script src="<?php echo $app_url?>/js/af.min.js"></script> 
  
 	
 	<style>
	.invoice-box {
        margin: auto;
        padding: 30px;
        font-size: 16px;
        line-height: 24px;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        color: #555;
    }

    .invoice-box table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background-color: white; 
    }

    .invoice-box th, .invoice-box td {
        border: 1px solid #000; 
        padding: 8px;
        color: #000; 
    }

    .invoice-box th {
        font-weight: bold;
        text-align: left;
    }

    .invoice-box tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .invoice-box tr:hover {
        background-color: #f2f2f2;
    }

    .invoice-box th, .invoice-box td {
        padding: 15px;
        text-align: left;
    }

    .invoice-box h2 {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .invoice-box table table {
        margin: 0;
        padding: 0;
        border-collapse: collapse;
    }

    .invoice-box table table th,
    .invoice-box table table td {
        padding: 0;
        border: none;
    }

    .invoice-box table table th {
        font-weight: normal;
    }
    

	@media only screen and (max-width: 600px) {
		.invoice-box table tr.top table td {
			width: 100%;
			display: block;
			text-align: center;
		}

		.invoice-box table tr.information table td {
			width: 100%;
			display: block;
			text-align: center;
		}
	}

	/** RTL **/
	.invoice-box.rtl {
		direction: rtl;
		font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
	}

	.invoice-box.rtl table {
		text-align: right;
	}

	.invoice-box.rtl table tr td:nth-child(2) {
		text-align: left;
	}
	</style>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js" ></script>

 

	<script type="text/javascript">
	$(document).ready(function($) 
	{ 

		$(document).on('click', '.btn_print', function(event) 
		{
			event.preventDefault();
			
			var element = document.getElementById('container_content'); 

			//easy
			//html2pdf().from(element).save();

			//custom file name
			//html2pdf().set({filename: 'code_with_mark_'+js.AutoCode()+'.pdf'}).from(element).save();


			//more custom settings
			var opt = 
			{
			  margin:       1,
			  filename:     'pageContent_'+js.AutoCode()+'.pdf',
			  image:        { type: 'jpeg', quality: 0.98 },
			  html2canvas:  { scale: 2 },
			  jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
			};

			html2pdf().set(opt).from(element).save();

			 
		});

 
 
	});
	</script>

	 

</head>
<body>

<div class="text-center" style="padding:20px;">
	<input type="button" id="rep" value="Print" class="btn btn-info btn_print">
</div>


<div class="container_content" id="container_content">
    <div class="invoice-box">
        <h2>HISTOPATHOLOGY REPORT</h2>
            <table>
                <tr>
                    <th>Lab No:</th>
                    <td>HPL2402-03393</td>
                    <th>SI No:</th>
                    <td>2212-45226</td>
                </tr>
                <tr>
                    <th>Patient:</th>
                    <td>Ms. Mim</td>
                    <td colspan="5">
                        <table>
                            <tr>
                                <th>Age:</th>
                                <td>15 Yrs</td>
                                <th>Sex:</th>
                                <td>Female</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th>Refd. by:</th>
                    <td>PAN PACIFIC HOSPITAL</td>
                    <th>Specimen:</th>
                    <td>Tissue from ...</td>
                </tr>
                <tr>
                    <th colspan="2">Clinical Details:</th>
                    <td colspan="4">[Your dynamic clinical details here]</td>
                </tr>
                <tr>
                    <th>Specimen received on:</th>
                    <td>05/02/2024 11:58 AM</td>
                    <th>Reported on:</th>
                    <td>[Your dynamic reported on date here]</td>
                </tr>
            </table>
    </div>
    <div>
        <h2>Gross description:</h2>
    </div>
</div>





</body>
</html>