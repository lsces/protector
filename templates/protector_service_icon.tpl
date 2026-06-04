{if !$serviceHash && $gContent->mInfo}
	{assign var=serviceHash value=$gContent->mInfo}
{/if}

{if !empty($serviceHash.is_hidden) && $serviceHash.is_hidden=='y'}
	{assign var=securityLabel value="Hidden"}
{/if}
{if !empty($serviceHash.is_private) && $serviceHash.is_private=='y'}
	{assign var=securityLabel value="Private"}
{/if}
{if !empty($serviceHash.access_answer) && $serviceHash.access_answer}
	{assign var=securityLabel value="Password Required"}
{/if}
{if !empty($securityLabel)}
	{biticon ipackage="icons" iname="lock" ipackage="icons" iexplain=$securityLabel iforce=icon_text}
{/if}
