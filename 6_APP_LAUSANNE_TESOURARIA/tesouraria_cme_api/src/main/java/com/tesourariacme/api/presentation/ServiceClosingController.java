package com.tesourariacme.api.presentation;

import com.tesourariacme.api.application.SubmitServiceClosingUseCase;
import com.tesourariacme.api.domain.Envelope;
import com.tesourariacme.api.domain.ServiceClosing;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.stream.Collectors;

@RestController
@RequestMapping("/api/fechamento-culto")
@CrossOrigin(origins = "*") // Para MVP local e Flutter Web
public class ServiceClosingController {

    private final SubmitServiceClosingUseCase useCase;

    public ServiceClosingController(SubmitServiceClosingUseCase useCase) {
        this.useCase = useCase;
    }

    @PostMapping
    public ResponseEntity<?> submitClosing(@RequestBody ServiceClosingRequest request) {
        try {
            ServiceClosing closing = new ServiceClosing();
            closing.setServiceDate(request.getServiceDate());
            closing.setMainTreasurer(request.getMainTreasurer());
            closing.setCoTreasurer(request.getCoTreasurer());
            closing.setPhysicalTotal(request.getPhysicalTotal());
            closing.setUnidentifiedDizimoTotal(request.getUnidentifiedDizimoTotal());
            closing.setUnidentifiedOfertaTotal(request.getUnidentifiedOfertaTotal());
            closing.setUnidentifiedVotoTotal(request.getUnidentifiedVotoTotal());
            
            if (request.getIdentifiedEntries() != null) {
                closing.setIdentifiedEntries(request.getIdentifiedEntries().stream().map(req -> {
                    Envelope env = new Envelope();
                    env.setMemberName(req.getMemberName());
                    env.setType(req.getType());
                    env.setAmount(req.getAmount());
                    return env;
                }).collect(Collectors.toList()));
            }

            ServiceClosing saved = useCase.execute(closing);
            return ResponseEntity.ok(saved);
        } catch (IllegalArgumentException e) {
            return ResponseEntity.badRequest().body(e.getMessage());
        }
    }

    @GetMapping
    public ResponseEntity<?> getHistory() {
        return ResponseEntity.ok(useCase.getHistory());
    }

    @GetMapping("/{id}")
    public ResponseEntity<?> getById(@PathVariable Long id) {
        try {
            return ResponseEntity.ok(ServiceClosingDetailResponse.fromEntity(useCase.getById(id)));
        } catch (IllegalArgumentException e) {
            return ResponseEntity.notFound().build();
        }
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> deleteClosing(@PathVariable Long id) {
        try {
            useCase.deleteById(id);
            return ResponseEntity.noContent().build();
        } catch (IllegalArgumentException e) {
            return ResponseEntity.notFound().build();
        }
    }
}
