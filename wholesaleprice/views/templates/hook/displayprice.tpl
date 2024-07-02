<div class="wholesale-price">
    {if isset($wholesale_price)}
        {l s='Wholesale Price:' mod='wholesaleprice'} {$wholesale_price}
    {else}
        <p>{l s='No wholesale price available' mod='wholesaleprice'}</p>
    {/if}
</div>