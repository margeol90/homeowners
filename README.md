<h1>HOMEOWNERS</h1>
Parses CSVs with names, into separate columns

<h2>Deployment</h2>
Clone repo and open terminal OR use Github spaces
Run command 'php artisan csv:parser {file_path}' and pass in a file/filepath to process

<h2>Tests</h2>  
Run 'php artisan test --filter=CSVParserTest'

</br>
</br>
</br>
</br>


> [!NOTE]  
> This parser will process most names, combinations and numbers of names.
> In its current form, it does not accommodate for foreign names or no titles.
> Allocated fields should be preferred over mixed formats
