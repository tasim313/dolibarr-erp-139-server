$( document ).ready(function() {

    $set_status = 1; // Gross Completed
    $('#scandate').val($.datepicker.formatDate('yy-mm-dd', new Date()));
    
    // $('#scanDataTable').DataTable({
    //   paging: false
    // });

    // script for tab steps
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    
        $("#scandate").focus();
        var href = $(e.target).attr('href');
        // alert ($("#dol_login").val()+ " - " + href );
        $("#readinvno").hide();
        switch (href) {
          case "#status1": $set_status = 1; break;
          case "#status2": $set_status = 2; break;
          case "#status3": $set_status = 3; break;
          case "#status4n5": 
            $set_status = $("input[name='flexRadioWaiting']:checked").val(); break;
          case "#status6n8": 
            $set_status = $("input[name='flexRadioAddReq']:checked").val(); break;
          case "#status7n9n12": 
            $set_status = $("input[name='flexRadioAddDone']:checked").val(); break;
          case "#status10": $set_status = 10; break;
          case "#status11": $set_status = 11; break;
          case "#status13": $set_status = 13; break;
          default: $set_status = 0; 
            $("#readinvno").show();
            break;
        }
        console.log($("#scandate").val(),"List status:" + $set_status);
        var $curr = $(".process-model  a[href='" + href + "']").parent();
        
        $('.process-model li').removeClass();
        
        $curr.addClass("active");
        $curr.prevAll().addClass("visited");
        $(".design-process-content").children("p").remove();
        // window.setTimeout(() => $("#scandate").focus(), 0);
        $.fn.listScans();
        
    });
    // end  script for tab steps

    $(document).on('change', '#readdate', function() {
        // do your things
        $.fn.listScans();
    });

    $.fn.listScans = function(){
      $scandate = $("#scandate").val();

      switch (true) {
        default:
          $.ajax({
            url: "http://192.168.1.139:8881/api/index.php/orders/"
              +"datetracking/"
              +$scandate+"/"
              +$set_status
              +"?DOLAPIKEY="+$("#dol_userapikey").val(), 
            type: 'get',
            contentType: "json",
            error: function(XMLHttpRequest, textStatus, errorThrown){
                $(".tab-pane.active").children(".design-process-content").children("h3").after("<p>"
                  + JSON.parse( XMLHttpRequest.responseText).error.message
                  +"</p>"
                  +"<p>------------------------------------------------------------</p>"
                  );              
                // alert('status:' + XMLHttpRequest.status + ', status text: ' + XMLHttpRequest.statusText);
            },
            success: function(data){
              $i = data.track_resnum;
              $(".tab-pane.active").children(".design-process-content").children("h3").after("<p>"
                +"------------------------------------------------------------</p>");            
              while($i>0){
                $i--;
                $(".tab-pane.active").children(".design-process-content").children("h3").after("<p>"
                  +(data.track_labno[$i])
                  + " - " +(data.track_create_time[$i])
                  + " - "+(data.track_login[$i])
                  + " - "+(data.track_status_name[$i])
                  + " - "+(data.track_status_section[$i])
                  +"</p>");
              }
              $(".tab-pane.active").children(".design-process-content").children("h3").after("<p>"
                +"<b>"+data.track_resnum+" scans listed:</b></p>");               
            }
          });  
          break;
      }
        
      console.log($scandate + "-" + $set_status + "-" + $("#dol_userid").val() + "-" + $("#dol_userapikey").val());
      // $("#scandate").val("");
      return false;      
    }

   



    $("input[name='flexRadioWaiting']").change(function() {
        $set_status = $("input[name='flexRadioWaiting']:checked").val();
        $("#scandate").focus();
        console.log("List status radio:" + $set_status,$("#scandate").val());
        $.fn.listScans();
    });
    $("input[name='flexRadioAddReq']").change(function() {
        $set_status = $("input[name='flexRadioAddReq']:checked").val();
        $("#scandate").focus();
        console.log("List status radio:" + $set_status,$("#scandate").val());
        $.fn.listScans();
    });
    $("input[name='flexRadioAddDone']").change(function() {
        $set_status = $("input[name='flexRadioAddDone']:checked").val();
        $("#scandate").focus();
        console.log("List status radio:" + $set_status,$("#scandate").val());
        $.fn.listScans();
    });   

    
    
    
    // function onScanSuccess(decodedText, decodedResult) {
    //   console.log(`Code scanned = ${decodedText}`, decodedResult);
    // }
    // var html5QrcodeScanner = new Html5QrcodeScanner(
    //   "qr-reader", { fps: 10, qrbox: 250 });
    //   html5QrcodeScanner.render(onScanSuccess);
    
    $.fn.listScans();
});