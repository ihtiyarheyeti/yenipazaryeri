import { Input, Checkbox, Button } from "antd";
import { useState } from "react";

export function ProductFilters({onChange}:{onChange:(f:{search:string;onlyUnmapped:boolean})=>void}){
  const [s,setS]=useState(''); 
  const [u,setU]=useState(false);
  
  return (
    <div style={{display:'flex',gap:8,alignItems:'center'}}>
      <Input 
        placeholder="Ara (ad/SKU)" 
        value={s} 
        onChange={e=>setS(e.target.value)} 
        style={{width:220}}
        onPressEnter={() => onChange({search:s,onlyUnmapped:u})}
      />
      <Checkbox 
        checked={u} 
        onChange={e=>setU(e.target.checked)}
      >
        Sadece eşleşmemiş
      </Checkbox>
      <Button 
        type="primary"
        onClick={()=>onChange({search:s,onlyUnmapped:u})}
      >
        Uygula
      </Button>
    </div>
  );
}
