# Itinero Routing

The goal of this tool is to calculate routes between 2 datasets of points with *Routing API* from [*Itinero*](http://www.itinero.tech) : <https://github.com/itinero/routing-api>.  
*Itinero Routing API* uses the data and geometry from [*OpenStreetMap*](https://openstreetmap.org/) !

## Install

The tool only requires **PHP 7.0+**.

```
git clone https://github.com/geo6/itinero-routing
cd itinero-routing/

# Install Composer
curl -sS https://getcomposer.org/installer | php

# Install dependencies
php composer.phar install
```

## Usage

```
php route.php --from=data/testFrom.csv --to=data/testTo.csv --api=http://localhost/belgium/
```

### Options

| Option      | Default   | Description                                                                  |
|-------------|-----------|------------------------------------------------------------------------------|
| **--from**  | -         | **Required** - CSV file containing **from** points                           |
| **--to**    | -         | **Required** - CSV file containing **to** points                             |
| **--api**   | -         | **Required** - URL to *Itinero Routing API*                                  |
| --profile   | `car`     | Routing profile used by *Itinero Routing API*                                |
| --mode      | `fastest` | Minimize time with `fastest` value, or distance with `closest` value         |
| --no-header | -         | Add this option if your CSV file does not contain columns name in first line |

### File structure

Your files must be valid CSV (Comma-separated values) files.  
The structure is the following :
- Longitude (*float*)
- Latitude (*float*)
- Identifier (*string* or *integer*)
- Any additional information

Have a look at `data/testFrom.csv` or `data/testTo.csv` if necessary.
