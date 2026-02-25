namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ArrayExport implements FromArray
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data->toArray();
    }

    public function array(): array
    {
        return $this->data;
    }
}