<?php

namespace WHMCS\Module\Widget;

use AdminLang;
use App;
use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\Module\AbstractWidget;
use WHMCS\Order\Order;


class UpcomingRenewals extends AbstractWidget
{
    protected $title = 'Upcoming Renewals';
    protected $description = 'Listing of Upcoming Renewals.';
    protected $weight = 100;
    protected $cache = false;
    protected $cacheExpiry = 6 * 60;
    protected $columns = 2;
    protected $requiredPermission = 'View Income Totals';

    // WIDGET PARAMETERS
    private $_datefield = 'nextinvoicedate'; // [nextinvoicedate,nextduedate]
    private $_daysout = 30;

    public function getData()
    {
        $DateCutoff = Carbon::now()->addDays($this->_daysout);

        $renewals = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->select('tblhosting.*', 'tblproducts.name as product')
            ->whereDate($this->_datefield, '<=',  $DateCutoff)
            ->whereDate($this->_datefield, '>=', Carbon::now())
            // ->where('server', '>', 0)
            ->where('amount', '>', 0)
            ->where("domainstatus", "active")
            ->OrderBy('nextduedate');

        return array('renewals' => $renewals->get());
    }

    public function generateOutput($data)
    {
        $content = '<table bgcolor="#cccccc" align="center" style="margin-bottom:5px;width:100%;" cellspacing="1">
<tr bgcolor="#efefef" style="text-align:center;font-weight:bold;"><td>Domain</td><td>Billing Cycle</td><td>Payment Method</td><td>Next Due Date</td><td>Amount</td></tr>';

        // $result = mysql_query("SELECT * FROM `tblhosting` WHERE DATEDIFF(`nextduedate`, Now()) $range AND `server` > 0 ORDER BY `nextduedate` ASC");
        foreach ($data['renewals'] as $r) {
            $content .= '<tr bgcolor="#ffffff" style="text-align:center;"><td>' . $r->product . ' - <a href="clientshosting.php?userid=' . $r->userid . '&id=' . $r->id . '">' . $r->domain . '</a></td><td>' . $r->billingcycle . '</td><td>' . $r->paymentmethod . '</td><td>' . fromMySQLDate($r->nextduedate) . '</td><td>' . formatCurrency($r->amount) . '</td></tr>';
        }
        if (empty($data['renewals'])) {
            $content =  '<tr bgcolor="#ffffff" style="text-align:center;"><td colspan="7">No upcoming hosting renewals</td></tr>';
        }
        $content .= '</table>';

        return $content;
    }
}
