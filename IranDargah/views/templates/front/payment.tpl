{capture name=path}
	{l s='پرداخت از طریق درگاه پرداخت ایران درگاه' mod='IranDargah'}
{/capture}


<h1 class="page-heading">{l s='جزئیات سفارش' mod='IranDargah'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nb_products <= 0}
    <p class="alert alert-warning">
        {l s='سبد خرید شما خالی است.' mod='IranDargah'}
    </p>
{else}
    <form action="{$link->getModuleLink('IranDargah', 'validation', [], true)|escape:'html'}" method="post">
		<div class="box cheque-box">
			<h3 class="page-subheading">
				{l s='درگاه پرداخت ایران درگاه' mod='IranDargah'}
			</h3>
			<p class="cheque-indent">
				<strong class="dark">
					{l s='شما پرداخت از طریق درگاه پرداخت ایران درگاه را انتخاب کرده‌اید. در ادامه جزییات سفارش شما قرار دارد:' mod='IranDargah'}
				</strong>
			</p>
			<ul>
				<li>
					{l s='مبلغ کل سفارش شما:' mod='IranDargah'}
					<span id="amount" class="price">{displayPrice price=$total_amount}</span>
					<input type="hidden" name="currency_payment" value="{$cart_currency}" />
				</li>
				<li>
					{l s='لطفا سفارش خود را با کلیک بر روی دکمه "تایید و پرداخت" نهایی کنید.' mod='IranDargah'}
				</li>
			</ul>
		</div><!-- .cheque-box -->

		<p class="cart_navigation clearfix" id="cart_navigation">
			<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
				<i class="icon-chevron-left"></i>{l s='روش های پرداخت دیگر' mod='IranDargah'}
			</a>
			<button class="button btn btn-default button-medium" type="submit">
				<span>{l s='تایید و پرداخت' mod='IranDargah'}<i class="icon-chevron-right right"></i></span>
			</button>
		</p>
    </form>
{/if}