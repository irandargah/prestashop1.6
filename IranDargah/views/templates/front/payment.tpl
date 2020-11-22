{capture name=path}
	{l s='IranDargah Payment Gateway' mod='IranDargah'}
{/capture}


<h1 class="page-heading">{l s='Order summary' mod='IranDargah'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nb_products <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='IranDargah'}
    </p>
{else}
    <form action="{$link->getModuleLink('IranDargah', 'validation', [], true)|escape:'html'}" method="post">
		<div class="box cheque-box">
			<h3 class="page-subheading">
				{l s='IranDargah Payment Gateway' mod='IranDargah'}
			</h3>
			<p class="cheque-indent">
				<strong class="dark">
					{l s='You have chosen to pay via IranDargah Payment Gateway.' mod='IranDargah'} {l s='Here is a short summary of your order:' mod='IranDargah'}
				</strong>
			</p>
			<ul>
				<li>
					{l s='The total amount of your order is' mod='IranDargah'}
					<span id="amount" class="price">{displayPrice price=$total_amount}</span>
					{if $use_taxes == 1}{l s='(tax incl.)' mod='IranDargah'}{/if}
					<input type="hidden" name="currency_payment" value="{$cart_currency}" />
				</li>
				<li>
					{l s='Please confirm your order by clicking "Confirm and Proceed to Payment"' mod='IranDargah'}.
				</li>
			</ul>
		</div><!-- .cheque-box -->

		<p class="cart_navigation clearfix" id="cart_navigation">
			<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
				<i class="icon-chevron-left"></i>{l s='Other payment methods' mod='IranDargah'}
			</a>
			<button class="button btn btn-default button-medium" type="submit">
				<span>{l s='Confirm and Proceed to Payment' mod='IranDargah'}<i class="icon-chevron-right right"></i></span>
			</button>
		</p>
    </form>
{/if}