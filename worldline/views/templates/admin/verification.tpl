<!DOCTYPE html>
  <html lang="en">
  <head>
    <style>
    table {
      font-family: arial, sans-serif;
      border-collapse: collapse;
      width: 50%;
    }

    td, th {
      border: 1px solid #dddddd;
      text-align: left;
      padding: 8px;
    }
    </style>
  </head>
<div class="container-fluid">    
      
    </div>
    <meta charset = "UTF-8" />
    <form id="form"  class="form-inline" method="POST">

      Merchant Ref No:<input type="text" name="token"  placeholder="Merchant Ref No." required/>&nbsp;
      Date:    <input type="date" name="date" placeholder="dd-mm-yyyy" required/>  
      <input type="hidden" name="mrctCode" value='{$data.mrctCode}'/>          
      <input type="hidden" name="currency" value='{$data.currency}'/>          
         &nbsp; &nbsp;  <button id="btnSubmit" type="submit" class="btn btn-primary" name="submit" value="Submit" >Submit</button>
      </form>
      <br>
    <br>
      <p></p>
    </div>
    <br>
    <br>
      </div>
    </div>
  </div>
</div>
<script>
$(document).ready(function(){
  $("#btnSubmit").click(function(e){
    e.preventDefault();
    var str = $("#form").serializeArray();
 	
    function formatDate (dateString) {
   var p = dateString.split(/\D/g);
  return [p[2],p[1],p[0] ].join("-");
  }
if(str[1].value !='' && str[0].value !='' && str[2].value !=''){
var dateformated = formatDate(str[1].value);

    var data = {
   "merchant": {
    "identifier": str[2].value
  },
  "transaction": {
    "deviceIdentifier": "S",
    "currency": str[3].value,
     "identifier": str[0].value,        
     "dateTime": dateformated,  
    "requestType": "O"
  }
};

var myJSON = JSON.stringify(data);
    
    $.ajax({
      type: 'POST',
      url: "https://www.paynimo.com/api/paynimoV2.req",
      data: myJSON,
      beforeSend: function() {
        $("p").html("");
        $("p").append('Loading......');
    },
      success: function(resultData) { 
        console.log(resultData);
        var response=JSON.stringify(resultData);
        var status_code = resultData.paymentMethod.paymentTransaction.statusCode;
        var status_message = resultData.paymentMethod.paymentTransaction.statusMessage;
        var identifier = resultData.paymentMethod.paymentTransaction.identifier;
        var amount = resultData.paymentMethod.paymentTransaction.amount;
        var errorMessage = resultData.paymentMethod.paymentTransaction.errorMessage;
        var dateTime = resultData.paymentMethod.paymentTransaction.dateTime;
        var merchantTransactionIdentifier = resultData.merchantTransactionIdentifier;

        $("p").html("");
        $("p").append(         
      "<table>" +
          "<tr>"+
            "<th>Status Code</th>"+
            "<th>" + status_code +"</th>"+
          "</tr>"+
          "<tr>" +
            "<th>Merchant Transaction Reference No</th>"+
            "<th>" + merchantTransactionIdentifier +"</th>"+
          "</tr>"+
          "<tr>" +
            "<th>TPSL Transaction ID</th>"+
            "<th>" + identifier +"</th>"+
          "</tr>"+
          "<tr>" +
            "<th>Amount</th>"+
            "<th>" + amount +"</th>"+
          "</tr>"+
          "<tr>" +
            "<th>Message</th>"+
            "<th>" + errorMessage +"</th>"+
          "</tr>"+ 
          "<tr>" +
            "<th>Status Message</th>"+
            "<th>" + status_message +"</th>"+
          "</tr>"+
           "<tr>" +
            "<th>Date Time</th>"+
            "<th>" + dateTime +"</th>"+
          "</tr>"+
      "</table>"+
    "</div>"+
    "</div>");
        
      }
});
  }else{
    alert('Please Fill All Fields');
  }
  });
});
</script>
