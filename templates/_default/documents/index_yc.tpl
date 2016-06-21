<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; utf-8">
<meta name="author" content=""/>
<meta name="copyright" content="" />

<title></title>
<style type="text/css">
body {
	{$Containers.Body.style}
}

img.signpic {
	margin-top: 0mm;
}

div#head_sender {
	{$Containers.Header_Recipient.style}
}

div#header {
	{$Containers.Header.style}
}

div#head_left {
	{$Containers.Header_Box_Left.style}
}

div#head_right {
	{$Containers.Header_Box_Right.style}
}

div#head_bottom {
	{$Containers.Header_Box_Bottom.style}
}

div#content {
	{$Containers.Content.style}
}

td {
	{$Containers.Td.style}
}

td.name {
	{$Containers.Td_Name.style}
}

td.line {
	{$Containers.Td_Line.style}
}

td.head  {
	{$Containers.Td_Head.style}
}

#footer {
	{$Containers.Footer.style}
}

#footer span {
	padding-right: 5mm;
}

#amount {
	{$Containers.Content_Amount.style}
}

#sender {
	{$Containers.Header_Sender.style}
}

#info {
	margin-top: -10mm;
}
</style>

<body>
{* additional information for International Handling *}
{if $User.shipping.country}
	{assign var="billedcountry" value=$User.shipping.country->id|intval}
{else}
	{assign var="billedcountry" value=$User.billing.country->id|intval}
{/if}

{assign var=isForeignCountry value=false}
{if $billedcountry != $smarty.session.Shopware.shopLocaleId}
	{assign var=isForeignCountry value=true}
{/if}

{* start 3-time printer loop *}
{assign var="includefile" value=$smarty.current_dir|cat:'/ycubecontent.tpl'}
{if $isForeignCountry}
	{section name=foo start=0 loop=3 step=1}
		{include file=$includefile Pages=$Pages}
	{/section}
{else}
	{include file=$includefile Pages=$Pages}
{/if}

</body>
</html>