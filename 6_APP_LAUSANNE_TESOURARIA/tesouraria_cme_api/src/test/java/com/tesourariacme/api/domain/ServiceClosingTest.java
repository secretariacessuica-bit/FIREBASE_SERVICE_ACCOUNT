package com.tesourariacme.api.domain;

import org.junit.jupiter.api.Test;
import java.math.BigDecimal;
import java.util.List;
import static org.junit.jupiter.api.Assertions.*;

public class ServiceClosingTest {

    @Test
    public void testCalculateTotalsAndValidate_AcceptsWhenDifferenceIsZero() {
        ServiceClosing closing = new ServiceClosing();
        
        // Identificado 190
        closing.setIdentifiedEntries(List.of(
            new Envelope(null, "João", EnvelopeType.DIZIMO, new BigDecimal("100")),
            new Envelope(null, "Maria", EnvelopeType.OFERTA, new BigDecimal("90"))
        ));
        
        // Não identificado 60
        closing.setUnidentifiedDizimoTotal(new BigDecimal("20"));
        closing.setUnidentifiedOfertaTotal(new BigDecimal("30"));
        closing.setUnidentifiedVotoTotal(new BigDecimal("10"));
        
        // Físico 250
        closing.setPhysicalTotal(new BigDecimal("250"));
        
        // → ACEITA
        assertDoesNotThrow(() -> closing.calculateTotalsAndValidate());
        
        assertEquals(0, new BigDecimal("190").compareTo(closing.getIdentifiedTotal()));
        assertEquals(0, new BigDecimal("60").compareTo(closing.getUnidentifiedTotal()));
        assertEquals(0, new BigDecimal("250").compareTo(closing.getRegisteredTotal()));
    }

    @Test
    public void testCalculateTotalsAndValidate_RejectsWhenDifferenceIsNonZero() {
        ServiceClosing closing = new ServiceClosing();
        
        // Identificado 190
        closing.setIdentifiedEntries(List.of(
            new Envelope(null, "João", EnvelopeType.DIZIMO, new BigDecimal("100")),
            new Envelope(null, "Maria", EnvelopeType.OFERTA, new BigDecimal("90"))
        ));
        
        // Não identificado 50
        closing.setUnidentifiedDizimoTotal(new BigDecimal("20"));
        closing.setUnidentifiedOfertaTotal(new BigDecimal("20"));
        closing.setUnidentifiedVotoTotal(new BigDecimal("10"));
        
        // Físico 250
        closing.setPhysicalTotal(new BigDecimal("250"));
        
        // → REJEITA (diferença 10)
        IllegalArgumentException thrown = assertThrows(IllegalArgumentException.class, () -> {
            closing.calculateTotalsAndValidate();
        });
        assertEquals("Fechamento inválido: total físico difere do total registrado.", thrown.getMessage());
    }

    @Test
    public void testCalculateTotalsAndValidate_SumsUnidentifiedCorrectly() {
        ServiceClosing closing = new ServiceClosing();
        
        // Dízimo não identificado 20
        closing.setUnidentifiedDizimoTotal(new BigDecimal("20"));
        // Oferta não identificada 30
        closing.setUnidentifiedOfertaTotal(new BigDecimal("30"));
        // Voto não identificado 10
        closing.setUnidentifiedVotoTotal(new BigDecimal("10"));
        
        closing.setPhysicalTotal(new BigDecimal("60"));
        
        closing.calculateTotalsAndValidate();
        
        // → unidentifiedTotal = 60
        assertEquals(0, new BigDecimal("60").compareTo(closing.getUnidentifiedTotal()));
    }
}
