{if !$serviceHash and $gContent->mInfo}
	{assign var=serviceHash value=$gContent->mInfo}
{/if}

{if !empty($serviceHash.is_hidden) and $serviceHash.is_hidden=='y'}
	{assign var=securityLabel value="Hidden"}
{/if}
{if !empty($serviceHash.is_private) and $serviceHash.is_private=='y'}
	{assign var=securityLabel value="Private"}
{/if}
{if !empty($serviceHash.access_answer) and $serviceHash.access_answer}
	{assign var=securityLabel value="Password Required"}
{/if}
{if !empty($securityLabel)}
	{booticon iname="icon-lock" ipackage="icons" iexplain=$securityLabel iforce=icon_text}
{/if}
