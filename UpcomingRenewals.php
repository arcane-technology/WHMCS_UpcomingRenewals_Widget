<?php

namespace WHMCS\Module\Widget;

use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\Module\AbstractWidget;

/**
 * Upcoming Renewals Widget.
 *
 * @copyright Copyright (c) Arcane Technology Solutions, LLC 
 * @license GPLv3 https://github.com/arcane-technology/WHMCS_UpcomingRenewals_Widget/blob/a86ee665fc3a57be854136c4161508ff00c1dc0f/LICENSE
 */
class UpcomingRenewals extends AbstractWidget
{
    protected $title = 'Upcoming Renewals';
    protected $description = 'Listing of Upcoming Renewals.';
    protected $weight = 100;
    protected $cache = true;
    protected $cacheExpiry = 6 * 60;
    // protected $columns = 2;
    protected $requiredPermission = 'View Income Totals';

    // WIDGET PARAMETERS
    private $_datefield = 'nextinvoicedate'; // [nextinvoicedate,nextduedate]
    private $_daysout = 30;

    public function getData()
    {
        $DateCutoff = Carbon::now()->addDays($this->_daysout);
        
        $result = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->join('tblclients', 'tblhosting.userid', '=', 'tblclients.id')
            ->select('tblhosting.*', 'tblclients.firstname', 'tblclients.lastname', 'tblclients.companyname', 'tblproducts.name as product')
            ->whereDate($this->_datefield, '<=', $DateCutoff)
            ->whereDate($this->_datefield, '>=', Carbon::now())
            // ->where('server', '>', 0)
            ->where('amount', '>', 0)
            ->where("domainstatus", "active")
            ->OrderBy('nextduedate')
            ->get();
       
        return array('renewals' => json_decode(json_encode($result), True));
    }

    public function generateOutput($data)
    {
  
        $renewals = array();
        foreach ($data['renewals'] as $r) {

            $renewals[] = "<div class='row ' style='padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid rgb(238, 238, 238)'>
              <div class='col-md-8'  style='white-space: nowrap; overflow: hidden; text-overflow: clip;'>
                    <a href='clientssummary.php?userid=$r->id' class='text-info'>
                        {$r['firstname']} {$r['lastname']} " . ($r['companyname'] ? ' (' . $r['companyname'] . ')' : '') . "
                    </a>
                </div>
                  <div class='col-md-4 text-right' style='white-space: nowrap;'>
                    <span class='text-success'>" . formatCurrency($r['amount']) . "</span>
                </div>
                <div class='col-md-9'>
                    <strong>{$r['product']}</strong> <span class='small'> <a href='clientshosting.php?userid={$r['userid']}&id={$r['id']} class='link'>{$r['domain']}</a></span>
                </div>
                  <div class='col-md-3 '>
                    <em>" . fromMySQLDate($r['nextduedate']) . "</em>
                </div>
              
                
              
            </div>";
        }

        if (count($renewals) == 0) {
            $renewals[] = 'No upcoming renewals found.';
        }

        $renewalOutput = implode($renewals);

        return '<div class="widget-content-padded">' . $renewalOutput . '</div>';
    }
}
