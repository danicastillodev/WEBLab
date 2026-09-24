import { es } from 'date-fns/locale';
import ReactDatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

function parseDate(str) {
    if (!str) return null;
    const [y, m, d] = str.split('/');
    const date = new Date(y, m - 1, d);
    return isNaN(date.getTime()) ? null : date;
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}/${m}/${d}`;
}

export default function DatePickerInput({ id, value, onChange, className = '', maxDate, minDate }) {
    return (
        <ReactDatePicker
            id={id}
            selected={parseDate(value)}
            onChange={(date) => onChange(date ? formatDate(date) : '')}
            dateFormat="yyyy/MM/dd"
            locale={es}
            placeholderText="aaaa/mm/dd"
            autoComplete="off"
            maxDate={maxDate}
            minDate={minDate}
            className={`rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${className}`}
            wrapperClassName="w-full"
            popperPlacement="bottom-start"
        />
    );
}
