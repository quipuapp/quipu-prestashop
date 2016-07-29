{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
    <h3>{l s='SYNCHRONIZE YOUR STORE EXISTING INVOICES WITH QUIPU' mod='quipu'}</h3>
    {if $synchronization == 0}
        <div class="alert alert-info">
            <ul class="list-unstyled">
                <li>{l s='If you have just started and have no invoices or confirmed payments, you can skip this step.' mod='quipu'}</li>
                <li>{l s='However, if you have existing invoices in your Prestashop, you can synchronize them with Quipu to have all your income centralized in one place. Quipu automatically copies the serial numbers from your existing invoices.' mod='quipu'}</li>
                <li>{l s='For refunds, the letter "R" will be added as a prefix, unlike the rest. For example, an amending invoice numbered "13" in Prestashop will be imported as "R13" in Quipu.' mod='quipu'}</li>
            </ul>
        </div>   
        <form method="post" action="{$url_form|escape:'html':'UTF-8'}" class="defaultForm form-horizontal">
            <div class="panel-footer">
                <button class="btn btn-default pull-left" name="submitSynchronization" value="1" type="submit">
                    <i class="process-icon-update"></i> {l s='Synchronize now' mod='quipu'}
                </button>
            </div>
        </form>
    {else}
        <div class="alert alert-info">
            <ul class="list-unstyled">
                <li>{l s='The synchronization is done.' mod='quipu'}</li>
            </ul>
        </div> 
    {/if}
</div>
