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
 <form id="form"  class="form-inline" method="post">

      From Date:<input type="date" name="fromdate" placeholder="dd-mm-yyyy" required/>&nbsp;
      To Date:    <input type="date" name="todate" placeholder="dd-mm-yyyy" required/>           
         &nbsp; &nbsp;  <button id="btnSubmit" type="submit" class="btn btn-primary" name="submit" value="Submit" >Submit</button>
      </form>
      <br>
    <br>
     {if isset($order_details) && is_array($order_details) && count($order_details) > 0}
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Delivery Date</th>
                <th>Total Amount</th>
                <th>Payment Method</th>
                <th>Order Status</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$order_details item=order_detail}
                <tr>
                    <td>{$order_detail.id_order}</td>
                    <td>{$order_detail.delivery_date}</td>
<td>{$order_detail.total_paid}</td>
<td>{$order_detail.payment}</td>
<td>{$order_detail.order_state_name}</td>

                </tr>
            {/foreach}
        </tbody>
    </table>
{else}
    <p>No order details found.</p>
{/if}    </div>
    <br>
    <br>